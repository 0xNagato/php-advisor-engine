<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class SmsData extends Data
{
    public function __construct(
        public string $phone,
        public string $text,
    ) {
    }
}
