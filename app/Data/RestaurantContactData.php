<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class RestaurantContactData extends Data
{
    public function __construct(
        public string $contact_name,
        public string $contact_phone,
        public bool $use_for_reservations,
    ) {
    }
}
