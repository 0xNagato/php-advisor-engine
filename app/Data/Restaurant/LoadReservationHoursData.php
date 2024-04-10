<?php

namespace App\Data\Restaurant;

use Spatie\LaravelData\Data;

class LoadReservationHoursData extends Data
{
    public function __construct(
        public array $startTimes,
        public array $endTimes,
        public array $selectedDays,
    ) {
    }
}
