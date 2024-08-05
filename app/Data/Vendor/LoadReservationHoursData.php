<?php

namespace App\Data\Vendor;

use Spatie\LaravelData\Data;

class LoadReservationHoursData extends Data
{
    public function __construct(
        public array $startTimes,
        public array $endTimes,
        public array $selectedDays,
    ) {}
}
