<?php

namespace App\Services;

use App\Actions\Region\GetUserRegion;
use App\Models\Region;
use App\Models\ScheduleWithBooking;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class ReservationService
 *
 * This service handles various operations related to reservations,
 * including finding available venues, adjusting reservation times,
 * and retrieving venue schedules.
 */
class ReservationService
{
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
        $currentDate = Carbon::now($this->region->timezone);

        /** @var Carbon $requestedTime */
        $requestedTime = Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone);

        $adjustedTime = $this->adjustTime($requestedTime->format('H:i:s'), $this->region->timezone);
        $this->reservationTime = $adjustedTime;

        $startTime = $this->calculateStartTime($adjustedTime);
        $endTime = $this->calculateEndTime($startTime, $this->region->timezone, $this->timeslotCount);

        $userTimezone = Auth::user()->timezone;

        Log::info('Time range for available venues', [
            'requested_time' => $requestedTime->format('H:i:s'),
            'requested_time_user_timezone' => $requestedTime->setTimezone($userTimezone)->format('g:i A'),
            'adjusted_time' => $adjustedTime,
            'adjusted_time_user_timezone' => Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)?->setTimezone($userTimezone)->format('g:i A'),
            'start_time' => $startTime,
            'start_time_user_timezone' => Carbon::createFromFormat('H:i:s', $startTime, $this->region->timezone)?->setTimezone($userTimezone)->format('g:i A'),
            'end_time' => $endTime,
            'end_time_user_timezone' => Carbon::createFromFormat('H:i:s', $endTime, $this->region->timezone)?->setTimezone($userTimezone)->format('g:i A'),
            'requested_date' => $this->date,
            'current_date' => $currentDate->toDateString(),
            'timezone' => $userTimezone,
            'party_size' => $this->getGuestCount(),
            'region_id' => $this->region->id,
            'timeslot_count' => $this->timeslotCount,
            'time_slot_offset' => $this->timeSlotOffset,
        ]);

        return Venue::available()
            ->where('region', $this->region->id)
            ->with(['schedules' => function ($query) use ($startTime, $endTime) {
                $query->where('booking_date', $this->date)
                    ->where('party_size', $this->getGuestCount())
                    ->where('start_time', '>=', $startTime)
                    ->where('start_time', '<=', $endTime);
            }])
            ->get();
    }

    /**
     * Adjust the reservation time if necessary based on current time and reservation rules.
     *
     * @param  string  $reservation  The requested reservation time
     * @param  string  $userTimezone  The user's timezone
     * @return string The adjusted (or original) reservation time
     */
    public function adjustTime(string $reservation, string $userTimezone): string
    {
        /** @var Carbon $reservationTime */
        $reservationTime = Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone);

        $currentTime = Carbon::now($this->region->timezone);

        /** @var Carbon $reservationDate */
        $reservationDate = Carbon::createFromFormat('Y-m-d', $this->date, $this->region->timezone);

        $adjustmentReason = 'No adjustment needed';
        $timeAdjusted = 0;
        $adjustedTime = $reservation;

        if ($reservationDate->isToday()) {
            $cutoffTime = $currentTime->copy()->addMinutes(self::MINUTES_PAST);

            if ($reservationTime->lt($cutoffTime)) {
                // Round up to the next 30-minute increment from the cutoff time
                $adjustedTime = $cutoffTime->copy()
                    ->addMinutes(30 - ($cutoffTime->minute % 30))
                    ->second(0)
                    ->format('H:i:s');

                $timeAdjusted = Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)
                    ?->diffInMinutes($reservationTime);

                $adjustmentReason = 'Requested time is within '.self::MINUTES_PAST.' minutes of current time';
            }
        }

        Log::info('Reservation time adjustment', [
            'requested_time' => $reservation,
            'requested_time_user_timezone' => Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone)?->setTimezone($userTimezone)->format('g:i A'),
            'adjusted_time' => $adjustedTime,
            'adjusted_time_user_timezone' => Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)?->setTimezone($userTimezone)->format('g:i A'),
            'current_time' => $currentTime->format('H:i:s'),
            'current_time_user_timezone' => $currentTime->setTimezone($userTimezone)->format('g:i A'),
            'requested_date' => $this->date,
            'user_timezone' => $userTimezone,
            'time_adjusted' => $timeAdjusted.' minutes',
            'adjustment_reason' => $adjustmentReason,
        ]);

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
            ->addMinutes(30 * ($timeslotCount - 1));
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
        $requestedTime = Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone);
        $adjustedTime = $this->adjustTime($this->reservationTime, $this->region->timezone);

        // Apply the offset
        $startTime = Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)
            ->subMinutes(min($this->timeSlotOffset, $this->timeslotCount - 1) * 30);

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

        return [
            'schedulesByDate' => $this->transformSchedules($schedulesByDate),
            'schedulesThisWeek' => $this->transformSchedules($schedulesThisWeek),
        ];
    }

    private function transformSchedules($schedules): array
    {
        return $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'schedule_template_id' => $schedule->schedule_template_id,
                'is_bookable' => $schedule->is_available && $schedule->remaining_tables > 0,
                'prime_time' => $schedule->prime_time,
                'time' => [
                    'value' => Carbon::parse($schedule->start_time)->format('g:i A'),
                    'raw' => $schedule->start_time,
                ],
                'date' => Carbon::parse($schedule->booking_date)->format('Y-m-d'),
                'fee' => '$'.number_format($schedule->effective_fee, 2),
                'has_low_inventory' => $schedule->remaining_tables <= 5,
            ];
        })->values()->toArray();
    }

    private function calculateStartTime(string $adjustedTime): string
    {
        $carbon = Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone);
        $offsetMinutes = $this->timeSlotOffset * 30;

        // Calculate how many slots we need to go back
        $slotsBack = min($this->timeSlotOffset, $this->timeslotCount - 1);

        return $carbon->subMinutes($slotsBack * 30)->format('H:i:s');
    }

    /**
     * Get schedules for a specific venue and date.
     *
     * @param  int  $venueId  The ID of the venue
     * @return Collection Collection of ScheduleWithBooking models
     */
    private function getSchedulesByDate(int $venueId): Collection
    {
        $reservationTime = $this->adjustTime($this->reservationTime, $this->region->timezone);
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
