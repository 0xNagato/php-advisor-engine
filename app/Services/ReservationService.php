<?php

namespace App\Services;

use App\Actions\Region\GetUserRegion;
use App\Enums\VenueStatus;
use App\Models\Region;
use App\Models\ScheduleWithBooking;
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
     * The region associated with the current user.
     */
    private readonly Region $region;

    /**
     * The minimum number of minutes in the future a reservation can be made while the date is today.
     * If a reservation is requested within this window, it will be adjusted to the next available time slot.
     */
    public const int MINUTES_PAST = 90;

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
        public int $timeSlotOffset = 0
    ) {
        // Set the user's region using the GetUserRegion action
        $this->region = GetUserRegion::run();
    }

    /**
     * Get available venues based on the reservation criteria.
     *
     * @return Collection Collection of available Venue models
     */
    public function getAvailableVenues(): Collection
    {
        /** @var Carbon $requestedTime */
        $requestedTime = Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone);

        $adjustedTime = $this->adjustTime($requestedTime->format('H:i:s'));
        $this->reservationTime = $adjustedTime;

        $startTime = $this->calculateStartTime($adjustedTime);
        $endTime = $this->calculateEndTime($startTime, $this->region->timezone, $this->timeslotCount);

        $currentTime = Carbon::now($this->region->timezone)->format('H:i:s');

        /** @var Collection<int, Venue> $venues */
        $venues = Venue::available()
            ->where('region', $this->region->id)
            ->withSchedulesForDate(
                date: $this->date,
                partySize: $this->getGuestCount(),
                startTime: $startTime,
                endTime: $endTime
            )
            ->get();

        $venues = $this->applyClosureRules($venues, $this->date);

        // Mark schedules as sold out if venue is past cutoff time
        $venues->each(function ($venue) use ($currentTime) {
            if ($venue->cutoff_time && $currentTime > $venue->cutoff_time) {
                $venue->schedules->each(function ($schedule) {
                    $schedule->is_available = true;
                    $schedule->remaining_tables = 0;
                    $schedule->is_bookable = false;
                });
            }
        });

        $sorted = $venues
            // Group venues based on availability and status, only considering middle 3 timeslots
            ->groupBy(function ($venue) {
                if ($venue->status === VenueStatus::PENDING) {
                    return 'pending';
                }

                // Get middle 3 schedules (indices 1,2,3 if timeslotCount is 5)
                $middleSchedules = $venue->schedules->slice(1, 3);

                $hasPrimeSlots = $middleSchedules->contains(fn ($s) => $s->is_bookable && $s->prime_time);
                if ($hasPrimeSlots) {
                    return 'prime_available';
                }

                $hasBookableSlots = $middleSchedules->contains(fn ($s) => $s->is_bookable);

                return $hasBookableSlots ? 'non_prime_available' : 'active_closed';
            })
            ->map(fn ($group) =>
                // Sort each group alphabetically A-Z
                $group->sortBy(fn ($venue) => strtolower($venue->name)))
            // Combine groups in desired order
            ->pipe(function ($groups) {
                return collect([])
                    ->concat($groups->get('prime_available', collect()))     // Prime slots first
                    ->concat($groups->get('non_prime_available', collect())) // Non-prime slots second
                    ->concat($groups->get('active_closed', collect()))       // Closed venues third
                    ->concat($groups->get('pending', collect()));            // SOON venues last
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
        /** @var Carbon $reservationTime */
        $reservationTime = Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone);

        $currentTime = Carbon::now($this->region->timezone);

        /** @var Carbon $reservationDate */
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
            $venue = Venue::find($venueId);
            $schedulesByDate = $this->applyClosureRules(new Collection([$venue]), $this->date)
                ->first()
                ?->schedules ?? $schedulesByDate;
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
     * @return Collection Collection of ScheduleWithBooking models
     */
    private function getSchedulesByDate(int $venueId): Collection
    {
        $reservationTime = $this->adjustTime($this->reservationTime);
        $startTime = $this->calculateStartTime($reservationTime);
        $endTimeForQuery = $this->calculateEndTime($startTime, $this->region->timezone, $this->timeslotCount);

        return ScheduleWithBooking::query()
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
     * @return Collection Collection of ScheduleWithBooking models
     */
    private function getSchedulesThisWeek(int $venueId): Collection
    {
        $currentDate = Carbon::now($this->region->timezone);
        $startTime = $this->calculateStartTime($this->reservationTime);

        return ScheduleWithBooking::query()
            ->with('venue')
            ->where('venue_id', $venueId)
            ->where('start_time', $startTime)
            ->where('party_size', $this->getGuestCount())
            ->whereDate('booking_date', '>', $currentDate)
            ->whereDate('booking_date', '<=', $currentDate->addDays(self::AVAILABILITY_DAYS))
            ->orderBy('booking_date')
            ->get();
    }
}
