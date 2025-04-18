<?php

namespace App\Data\Venue;

class LoadReservationHoursBlockData
{
    public function __construct(
        public array $openingHours = [],
        public array $selectedDays = []
    ) {}
}
