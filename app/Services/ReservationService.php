<?php

namespace App\Services;

use App\Actions\Region\GetUserRegion;
use App\Http\Resources\VenueScheduleResource;
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
     * The number of minutes to look into the future for available time slots.
     */
    public const int MINUTES_FUTURE = 120;

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
     */
    public function __construct(
        public string|Carbon $date,
        public int $guestCount,
        public string $reservationTime,
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
        $requestedDate = Carbon::createFromFormat('Y-m-d', $this->date, $this->region->timezone);
        $currentDate = Carbon::now($this->region->timezone);
        $requestedTime = Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone);

        // Round the requested time to the nearest 30-minute increment
        $requestedTime->minute(30 * round($requestedTime->minute / 30))->second(0);

        if ($currentDate->isSameDay($requestedDate)) {
            $this->reservationTime = $this->adjustTime($requestedTime->format('H:i:s'), $this->region->timezone);
        } else {
            $this->reservationTime = $requestedTime->format('H:i:s');
        }

        $endTime = $this->calculateEndTime($this->reservationTime, $this->region->timezone);

        $userTimezone = Auth::user()->timezone;

        Log::info('Time range for available venues', [
            'requested_time' => $requestedTime->format('H:i:s'),
            'requested_time_user_timezone' => $requestedTime->setTimezone($userTimezone)->format('g:i A'),
            'start_time' => $this->reservationTime,
            'start_time_user_timezone' => Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone)->setTimezone($userTimezone)->format('g:i A'),
            'end_time' => $endTime,
            'end_time_user_timezone' => Carbon::createFromFormat('H:i:s', $endTime, $this->region->timezone)->setTimezone($userTimezone)->format('g:i A'),
            'requested_date' => $this->date,
            'current_date' => $currentDate->toDateString(),
            'timezone' => $userTimezone,
            'party_size' => $this->getGuestCount(),
            'region_id' => $this->region->id,
            'minutes_past' => self::MINUTES_PAST,
            'minutes_future' => self::MINUTES_FUTURE,
        ]);

        return Venue::available()
            ->where('region', $this->region->id)
            ->with(['schedules' => function ($query) use ($endTime) {
                $query->where('booking_date', $this->date)
                    ->where('party_size', $this->getGuestCount())
                    ->where('start_time', '>=', $this->reservationTime)
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
    public function adjustTime($reservation, $userTimezone)
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone);
        $currentTime = Carbon::now($this->region->timezone);
        $reservationDate = Carbon::createFromFormat('Y-m-d', $this->date, $this->region->timezone);

        $adjustmentReason = 'No adjustment needed';
        $timeAdjusted = 0;
        $adjustedTime = $reservation;

        if ($reservationDate->isToday()) {
            $cutoffTime = $currentTime->copy()->addMinutes(self::MINUTES_PAST);

            if ($reservationTime->lte($cutoffTime)) {
                // Round up to the next 30-minute increment from the current time + MINUTES_PAST
                $adjustedTime = $currentTime->copy()->addMinutes(self::MINUTES_PAST)
                    ->minute(30 * ceil($currentTime->minute / 30))
                    ->second(0)
                    ->format('H:i:s');

                $timeAdjusted = Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)
                    ->diffInMinutes($currentTime);

                $adjustmentReason = 'Requested time is within '.self::MINUTES_PAST.' minutes of current time';
            }
        }

        $wasAdjusted = $adjustedTime !== $reservation;
        $userTimezone = Auth::user()->timezone;

        Log::info($wasAdjusted ? 'Reservation time adjusted' : 'Reservation time not adjusted', [
            'requested_time' => $reservation,
            'requested_time_user_timezone' => Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone)->setTimezone($userTimezone)->format('g:i A'),
            'original_time' => $reservation,
            'original_time_user_timezone' => Carbon::createFromFormat('H:i:s', $reservation, $this->region->timezone)->setTimezone($userTimezone)->format('g:i A'),
            'adjusted_time' => $adjustedTime,
            'adjusted_time_user_timezone' => Carbon::createFromFormat('H:i:s', $adjustedTime, $this->region->timezone)->setTimezone($userTimezone)->format('g:i A'),
            'current_time' => $currentTime->format('H:i:s'),
            'current_time_user_timezone' => $currentTime->setTimezone($userTimezone)->format('g:i A'),
            'requested_date' => $this->date,
            'reservation_date' => $reservationDate->toDateString(),
            'user_timezone' => $userTimezone,
            'party_size' => $this->guestCount,
            'region_id' => $this->region->id,
            'time_adjusted' => round(abs($timeAdjusted)).' minutes',
            'adjustment_reason' => $adjustmentReason,
        ]);

        return $adjustedTime;
    }

    /**
     * Calculate the end time for the reservation window.
     *
     * @param  string  $reservationTime  The start time of the reservation
     * @param  string  $userTimezone  The user's timezone
     * @return string The calculated end time
     */
    public function calculateEndTime($reservationTime, $userTimezone): string
    {
        $endTime = Carbon::createFromFormat('H:i:s', $reservationTime, $userTimezone)->addMinutes(self::MINUTES_FUTURE);
        $endTime->minute(30 * floor($endTime->minute / 30))->second(0);
        $limitTime = Carbon::createFromTime(23, 59, 0, $userTimezone);

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
        $headers = [];
        $start = Carbon::createFromFormat('H:i:s', $this->reservationTime);
        $end = Carbon::createFromFormat('H:i:s', $this->calculateEndTime($this->reservationTime, $this->region->timezone));

        // Generate headers in 30-minute intervals
        for ($time = $start; $time->lte($end); $time->addMinutes(30)) {
            $headers[$time->format('H:i:s')] = $time->format('g:i A');
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
    public function getVenueSchedules($venueId): array
    {
        $schedulesByDate = VenueScheduleResource::collection($this->getSchedulesByDate($venueId));

        if ($this->isDateToday()) {
            $schedulesThisWeek = VenueScheduleResource::collection($this->getSchedulesThisWeek($venueId));

            return [
                'schedulesByDate' => $schedulesByDate,
                'schedulesThisWeek' => $schedulesThisWeek,
            ];
        }

        return [
            'schedulesByDate' => $schedulesByDate,
        ];
    }

    /**
     * Get schedules for a specific venue and date.
     *
     * @param  int  $venueId  The ID of the venue
     * @return Collection Collection of ScheduleWithBooking models
     */
    public function getSchedulesByDate($venueId): Collection
    {
        $reservationTime = $this->adjustTime($this->reservationTime, $this->region->timezone);
        $endTimeForQuery = $this->calculateEndTime($reservationTime, $this->region->timezone);

        return ScheduleWithBooking::query()->where('venue_id', $venueId)
            ->where('booking_date', $this->date)
            ->where('party_size', $this->getGuestCount())
            ->where('start_time', '>=', $reservationTime)
            ->where('start_time', '<=', $endTimeForQuery)
            ->get();
    }

    /**
     * Get schedules for a specific venue for the upcoming week.
     *
     * @param  int  $venueId  The ID of the venue
     * @return Collection Collection of ScheduleWithBooking models
     */
    public function getSchedulesThisWeek($venueId): Collection
    {
        $currentDate = Carbon::now($this->region->timezone);

        return ScheduleWithBooking::query()->where('venue_id', $venueId)
            ->where('start_time', $this->reservationTime)
            ->where('party_size', $this->getGuestCount())
            ->whereDate('booking_date', '>', $currentDate)
            ->whereDate('booking_date', '<=', $currentDate->addDays(self::AVAILABILITY_DAYS))
            ->get();
    }

    /**
     * Check if the requested date is today.
     *
     * @return bool True if the requested date is today, false otherwise
     */
    protected function isDateToday(): bool
    {
        $requestedDate = Carbon::createFromFormat('Y-m-d', $this->date, $this->region->timezone);

        return $requestedDate->isToday();
    }
}
