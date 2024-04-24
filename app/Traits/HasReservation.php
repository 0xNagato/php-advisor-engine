<?php

namespace App\Traits;

use App\Models\Restaurant;
use Carbon\Carbon;

trait HasReservation
{
    public function getReservationTimeOptions(string $date, $onlyShowFuture = false): array
    {
        $userTimezone = auth()->user()->timezone;
        $currentDate = ($date === Carbon::now($userTimezone)->format('Y-m-d'));

        $currentTime = Carbon::now($userTimezone);
        $startTime = Carbon::createFromTime(Restaurant::DEFAULT_START_HOUR, 0, 0, $userTimezone);
        $endTime = Carbon::createFromTime(Restaurant::DEFAULT_END_HOUR, 0, 0, $userTimezone);

        $reservationTimes = [];

        for ($time = $startTime; $time->lte($endTime); $time->addMinutes(30)) {
            if ($onlyShowFuture && $currentDate && $time->lt($currentTime)) {
                continue;
            }
            $reservationTimes[$time->format('H:i:s')] = $time->format('g:i A');
        }

        return $reservationTimes;
    }

    // public function updatedData($data, $key): void
    // {
    //     // ... existing code ...
    // }

    /**
     * @param Carbon $currentDate
     * @param false|Carbon $requestedDate
     * @param mixed $userTimezone
     * @return mixed|string
     */
    public function getReservationTime(Carbon $currentDate, false|Carbon $requestedDate, mixed $userTimezone): mixed
    {
        if ($currentDate->isSameDay($requestedDate)) {
            $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
            $currentTime = Carbon::now($userTimezone);

            if ($reservationTime->copy()->subMinutes(self::MINUTES_PAST)->gt($currentTime)) {
                $reservationTime = $reservationTime->subMinutes(self::MINUTES_PAST)->format('H:i:s');
            } else {
                $reservationTime = $this->form->getState()['reservation_time'];
            }
        } else {
            $reservationTime = $this->form->getState()['reservation_time'];
        }
        return $reservationTime;
    }
}
