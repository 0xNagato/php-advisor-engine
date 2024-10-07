<?php

namespace App\Data\Venue;

use App\Models\Venue;
use Spatie\LaravelData\Data;

class SaveReservationHoursData extends Data
{
    public function __construct(
        public Venue $venue,
        public array $startTimes,
        public array $endTimes,
        public array $selectedDays,
    ) {}
}
