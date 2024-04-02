<?php

namespace App\Data\Restaurant;

use App\Models\Restaurant;
use Spatie\LaravelData\Data;

class SaveBusinessHoursData extends Data
{
    public function __construct(
        public Restaurant $restaurant,
        public array      $startTimes,
        public array      $endTimes,
        public array      $selectedDays,
    )
    {
    }
}
