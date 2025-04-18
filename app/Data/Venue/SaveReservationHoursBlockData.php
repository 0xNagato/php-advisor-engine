<?php

namespace App\Data\Venue;

use App\Models\Venue;

class SaveReservationHoursBlockData
{
    public function __construct(
        public Venue $venue,
        public array $selectedDays = [],
        public array $openingHours = []
    ) {}
}
