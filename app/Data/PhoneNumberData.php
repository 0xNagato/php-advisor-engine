<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class PhoneNumberData extends Data
{
    public function __construct(
        public string $phone,
        public string $country,
    ) {}
}
