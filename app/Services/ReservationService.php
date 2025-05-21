<?php

namespace App\Services;

use App\Actions\Region\GetUserRegion;
use App\Enums\VenueStatus;
use App\Enums\VenueType;
use App\Models\Region;
use App\Models\ScheduleWithBookingMV;
use App\Models\Venue;
use App\Traits\HandlesVenueClosures;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Class ReservationService
 *
 * This service handles various operations related to reservations,
 * including finding available venues, adjusting reservation times,
 * and retrieving venue schedules.
 */
class ReservationService
{
    use HandlesVenueClosures;

    /**
     * The minimum number of minutes in the future a reservation can be made while the date is today.
     * If a reservation is requested within this window, it will be adjusted to the next available time slot.
     */
    public const int MINUTES_PAST = 35;

    /**
     * The number of days in advance that reservations can be made.
     */
    public const int AVAILABILITY_DAYS = 3;

    /**
     * Constructor for ReservationService.
     *
     * @param  string|Carbon  $date  The reservation date
     * @param  int  $guestCount  The number of guests
     * @param  string  $reservationTime  The reservation time
     * @param  int  $timeslotCount  The number of timeslots to display
     * @param  int  $timeSlotOffset  The offset for the time slot
     */
    public function __construct(
        public string|Carbon $date,
        public int $guestCount,
        public string $reservationTime,
        public int $timeslotCount = 5,
        public int $timeSlotOffset = 0,
        public array $cuisines = [],
        public ?string $neighborhood = '',
        public ?Region $region = null,
        public array|string|null $specialty = []
    ) {
        $this->region ??= GetUserRegion::run();

        // Convert string specialty to array if needed
        if (is_string($this->specialty) && filled($this->specialty)) {
            $this->specialty = [$this->specialty];
        }
    }

    /**
     * Get available venues based on the reservation criteria.
     *
     * @return Collection Collection of available Venue models
     */
    public function getAvailableVenues(): Collection
    {
        /**
         * @var Carbon $requestedTime
         */
        $requestedTime = Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone);

        $adjustedTime = $this->adjustTime($requestedTime->format('H:i:s'));
        $this->reservationTime = $adjustedTime;

        $startTime = $this->calculateStartTime($adjustedTime);
        $endTime = $this->calculateEndTime($startTime, $this->region->timezone, $this->timeslotCount);

        $currentTime = Carbon::now($this->region->timezone)->format('H:i:s');

        // Parse the reservation date
        $reservationDate = Carbon::parse($this->date)->startOfDay();

        // Calculate the difference in days between today and the reservation date
        $dayDifference = today()->diffInDays($reservationDate) + 1;

        /**
         * @var Collection<int, Venue> $venues
         */
        $venues = Venue::available()
            ->where('region', $this->region->id)
            ->where(function ($query) {
                $statuses = [VenueStatus::ACTIVE, VenueStatus::PENDING];

                if (auth()->check() && auth()->user()->hasRole('super_admin')) {
                    $statuses[] = VenueStatus::HIDDEN;
                }

                $query->whereIn('status', $statuses);
            })
            ->where('venue_type', '!=', VenueType::HIKE_STATION)
            // Filter venues based on the advance booking window
            ->where(function ($query) use ($dayDifference) {
                $query->where('advance_booking_window', '=', 0) // Always include venues with 0 (no restrictions)
                    ->orWhere('advance_booking_window', '>=', $dayDifference); // Include venues with sufficient window
            })
            // Filter by concierge's allowed venues if applicable
            ->when(auth()->check() && auth()->user()->hasActiveRole('concierge') && auth()->user()->concierge,
                function ($query) {
                    $allowedVenueIds = auth()->user()->concierge->allowed_venue_ids ?? [];

                    // Only apply the filter if there are allowed venues
                    if (filled($allowedVenueIds)) {
                        // Ensure all IDs are integers
                        $allowedVenueIds = array_map('intval', $allowedVenueIds);
                        $query->whereIn('id', $allowedVenueIds);
                    }
                })
            ->when($this->cuisines, function ($query) {
                $query->where(function ($q) {
                    foreach ($this->cuisines as $cuisine) {
                        $q->orWhereJsonContains('cuisines', $cuisine);
                    }
                });
            })
            ->when($this->neighborhood, function ($query) {
                $query->where('neighborhood', $this->neighborhood);
            })
            ->when($this->specialty && count($this->specialty) > 0, function ($query) {
                // Handle an array of specialty values
                $query->where(function ($q) {
                    foreach ($this->specialty as $spec) {
                        $q->orWhereJsonContains('specialty', $spec);
                    }
                });
            })
            ->withSchedulesForDate(
                date: $this->date,
                partySize: $this->getGuestCount(),
                startTime: $startTime,
                endTime: $endTime
            )
            ->get();

        $venues = $this->applyClosureRules($venues, $this->date);

        // Mark schedules as sold out if the venue is past cutoff time
        $venues->each(function ($venue) use ($currentTime) {
            if ($venue->cutoff_time) {
                $currentTimeCarbon = Carbon::createFromFormat('H:i:s', $currentTime, $this->region->timezone);
                $cutoffTimeCarbon = Carbon::createFromFormat(
                    'H:i:s',
                    $venue->cutoff_time->format('H:i:s'),
                    $this->region->timezone
                );

                // Only apply cutoff time check if the reservation is for today
                $isToday = Carbon::parse($this->date, $this->region->timezone)->isToday();

                if ($isToday && $currentTimeCarbon->gt($cutoffTimeCarbon)) {
                    $venue->schedules->each(function ($schedule) {
                        $schedule->is_available = true;
                        $schedule->remaining_tables = 0;
                        $schedule->is_bookable = false;
                    });
                }
            }
        });

        $topTiers = $this->getTopTiers();

        $sorted = $venues
            // Group venues based on availability and status, only considering middle 3 timeslots
            ->groupBy(function ($venue) use ($topTiers) {
                if (filled($topTiers) && in_array((string) $venue->id, $topTiers, true)) {
                    return 'top_tiers';
                }
                if ($venue->status === VenueStatus::PENDING) {
                    return 'pending';
                }
                if ($venue->status === VenueStatus::HIDDEN) {
                    return 'hidden';
                }

                // Get middle 3 schedules (indices 1,2,3 if timeslotCount is 5)
                $middleSchedules = $venue->schedules->slice(1, 3);

                $hasPrimeSlots = $middleSchedules->contains(fn ($s) => $s->is_bookable && $s->prime_time);
                if ($hasPrimeSlots) {
                    return 'prime_available';
                }

                $hasBookableSlots = $middleSchedules->contains(fn ($s) => $s->is_bookable);

                return $hasBookableSlots ? 'non_prime_available' : (
                    $venue->schedules->contains(fn ($s) => $s->is_available && $s->remaining_tables === 0)
                        ? 'sold_out'
                        : 'closed'
                );
            })
            ->map(fn ($group) => // Sort each group alphabetically A-Z
            $group->sortBy(fn ($venue) => strtolower($venue->name)))
            // Combine groups in the desired order
            ->pipe(function ($groups) {
                return collect([])
                    ->concat($groups->get('hidden', collect()))           // Hidden venues first
                    ->concat($groups->get('top_tiers', collect()))        // Then top tiers
                    ->concat($groups->get('prime_available', collect()))  // Then prime slots
                    ->concat($groups->get('non_prime_available', collect())) // Then non-prime slots
                    ->concat($groups->get('sold_out', collect()))        // Then sold out venues
                    ->concat($groups->get('closed', collect()))          // Then closed venues
                    ->concat($groups->get('pending', collect()));        // SOON venues last
            });

        // Convert back to Eloquent Collection
        return Collection::make($sorted->values()->all());
    }

    /**
     * Adjust the reservation time if necessary based on current time and reservation rules.
     *
     * @param  string  $reservation  The requested reservation time
     * @return string The adjusted (or original) reservation time
     */
    public function adjustTime(string $reservation): string
    {
        /**
         * @var Carbon $reservationTime
         */
        $reservationTime = Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone);

        $currentTime = Carbon::now($this->region->timezone);

        /**
         * @var Carbon $reservationDate
         */
        $reservationDate = Carbon::createFromFormat('Y-m-d', $this->date, $this->region->timezone);

        $adjustedTime = $reservation;

        if ($reservationDate->isToday()) {
            $cutoffTime = $currentTime->copy()->addMinutes(self::MINUTES_PAST);

            if ($reservationTime->lt($cutoffTime)) {
                // Round up to the next 30-minute increment from the cutoff time
                $adjustedTime = $cutoffTime->copy()
                    ->addMinutes(30 - ($cutoffTime->minute % 30))
                    ->second(0)
                    ->format('H:i:s');
            }
        }

        return $adjustedTime;
    }

    /**
     * Calculate the end time for the reservation window.
     *
     * @param  string  $startTime  The start time of the reservation
     * @param  string  $timezone  The user's timezone
     * @param  int  $timeslotCount  The number of timeslots to display
     * @return string The calculated end time
     */
    public function calculateEndTime(string $startTime, string $timezone, int $timeslotCount): string
    {
        $endTime = Carbon::createFromFormat('H:i:s', $startTime, $timezone)
            ?->addMinutes(30 * ($timeslotCount - 1));
        $limitTime = Carbon::createFromTime(23, 59, 0, $timezone);

        // Ensure the end time doesn't exceed midnight
        return $endTime->gt($limitTime) ? '23:59:59' : $endTime->format('H:i:s');
    }

    /**
     * Get timeslot headers for the reservation window.
     *
     * @return array An array of timeslot headers
     */
    public function getTimeslotHeaders(): array
    {
        $adjustedTime = $this->adjustTime($this->reservationTime);

        // Apply the offset
        $startTime = Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)
            ?->subMinutes(min($this->timeSlotOffset, $this->timeslotCount - 1) * 30);

        $headers = [];
        for ($i = 0; $i < $this->timeslotCount; $i++) {
            $headers[] = $startTime->copy()->addMinutes($i * 30)->format('g:i A');
        }

        return $headers;
    }

    /**
     * Get the adjusted guest count, ensuring it's an even number.
     *
     * @return int The adjusted guest count
     */
    public function getGuestCount(): int
    {
        // If the guest count is odd, add 1 to make it even
        return $this->guestCount % 2 !== 0 ? $this->guestCount + 1 : $this->guestCount;
    }

    /**
     * Get venue schedules for a specific venue.
     *
     * @param  int  $venueId  The ID of the venue
     * @return array An array containing schedules by date and optionally schedules for this week
     */
    public function getVenueSchedules(int $venueId): array
    {
        $schedulesByDate = $this->getSchedulesByDate($venueId)->take($this->timeslotCount);
        $schedulesThisWeek = $this->getSchedulesThisWeek($venueId)->take($this->timeslotCount);

        // Apply closure rules if needed
        if ($this->isClosedDate($this->date)) {
            $venue = Venue::query()->find($venueId);
            $schedulesByDate = $this->applyClosureRules(new Collection([$venue]), $this->date)
                ->first()
                ?->schedules ?? $schedulesByDate;
        }

        // Check for cutoff time
        $venue = Venue::query()->find($venueId);
        if ($venue && $venue->cutoff_time) {
            $currentTime = Carbon::now($this->region->timezone);
            $cutoffTime = Carbon::parse($venue->cutoff_time, $this->region->timezone);

            // Only apply cutoff time check if the reservation is for today
            $isToday = Carbon::parse($this->date, $this->region->timezone)->isToday();

            if ($isToday && $currentTime->gt($cutoffTime)) {
                // Mark all schedules as unavailable if past cutoff time
                $schedulesByDate = $schedulesByDate->map(function ($schedule) {
                    $schedule->is_available = true;
                    $schedule->remaining_tables = 0;
                    $schedule->is_bookable = false;

                    return $schedule;
                });
            }
        }

        return [
            'schedulesByDate' => $this->transformSchedules($schedulesByDate),
            'schedulesThisWeek' => $this->transformSchedules($schedulesThisWeek),
        ];
    }

    private function transformSchedules($schedules): array
    {
        return $schedules->map(fn ($schedule) => [
            'id' => $schedule->id,
            'schedule_template_id' => $schedule->schedule_template_id,
            'is_bookable' => $schedule->is_available && $schedule->remaining_tables > 0,
            'prime_time' => $schedule->prime_time,
            'time' => [
                'value' => Carbon::parse($schedule->start_time)->format('g:i A'),
                'raw' => $schedule->start_time,
            ],
            'date' => Carbon::parse($schedule->booking_date)->format('Y-m-d'),
            'fee' => moneyWithoutCents($schedule->fee($this->guestCount), $this->region->currency),
            'has_low_inventory' => $schedule->remaining_tables <= 5,
        ])->values()->toArray();
    }

    private function calculateStartTime(string $adjustedTime): string
    {
        $carbon = Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone);

        // Calculate how many slots we need to go back
        $slotsBack = min($this->timeSlotOffset, $this->timeslotCount - 1);

        return $carbon?->subMinutes($slotsBack * 30)->format('H:i:s');
    }

    /**
     * Get schedules for a specific venue and date.
     *
     * @param  int  $venueId  The ID of the venue
     * @return Collection Collection of ScheduleWithBookingMV models
     */
    private function getSchedulesByDate(int $venueId): Collection
    {
        $reservationTime = $this->adjustTime($this->reservationTime);
        $startTime = $this->calculateStartTime($reservationTime);
        $endTimeForQuery = $this->calculateEndTime($startTime, $this->region->timezone, $this->timeslotCount);

        return ScheduleWithBookingMV::query()
            ->with('venue')
            ->where('venue_id', $venueId)
            ->where('booking_date', $this->date)
            ->where('party_size', $this->getGuestCount())
            ->where('start_time', '>=', $startTime)
            ->where('start_time', '<=', $endTimeForQuery)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules for a specific venue for the upcoming week.
     *
     * @param  int  $venueId  The ID of the venue
     * @return Collection Collection of ScheduleWithBookingMV models
     */
    private function getSchedulesThisWeek(int $venueId): Collection
    {
        $currentDate = Carbon::now($this->region->timezone);
        $startTime = $this->calculateStartTime($this->reservationTime);

        return ScheduleWithBookingMV::query()
            ->with('venue')
            ->where('venue_id', $venueId)
            ->where('start_time', $startTime)
            ->where('party_size', $this->getGuestCount())
            ->whereDate('booking_date', '>', $currentDate)
            ->whereDate('booking_date', '<=', $currentDate->addDays(self::AVAILABILITY_DAYS))
            ->orderBy('booking_date')
            ->get();
    }

    private function getTopTiers(): array
    {
        $envTopTiers = config('app.'.$this->region->id.'_top_tier_venues', '');
        if (blank($envTopTiers)) {
            return [];
        }

        // If numeric values are provided, convert them to strings for comparison
        return array_map('strval', array_filter(explode(',', (string) $envTopTiers)));
    }
}
