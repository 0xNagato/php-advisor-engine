<?php

namespace App\Actions\Reservations;

use App\Models\Region;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class GetReservationTimeOptions
{
    use AsAction;

    public function handle(string $date, $onlyShowFuture = false): array
    {
        $region = Region::user()->first();
        $currentDate = ($date === Carbon::now($region->timezone)->format('Y-m-d'));

        $currentTime = Carbon::now($region->timezone);
        $startTime = Carbon::createFromTime(Restaurant::DEFAULT_START_HOUR, 0, 0, $region->timezone);
        $endTime = Carbon::createFromTime(Restaurant::DEFAULT_END_HOUR, 0, 0, $region->timezone);

        $reservationTimes = [];

        for ($time = $startTime; $time->lte($endTime); $time->addMinutes(30)) {
            if ($onlyShowFuture && $currentDate && $time->lt($currentTime)) {
                continue;
            }
            $reservationTimes[$time->format('H:i:s')] = $time->format('g:i A');
        }

        return $reservationTimes;
    }
}
