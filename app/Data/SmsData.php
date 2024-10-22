<?php

namespace App\Data;

use InvalidArgumentException;
use Spatie\LaravelData\Data;

class SmsData extends Data
{
    public function __construct(
        public string $phone,
        public ?string $text = null,
        public ?string $templateKey = null,
        public ?array $templateData = []
    ) {
        throw_if(blank($text) && blank($templateKey), new InvalidArgumentException('Either text or templateKey must be provided'));
    }
}
