<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class NotificationPreferencesData extends Data
{
    public function __construct(
        public ?bool $mail = false,
        public ?bool $sms = false,
        public ?bool $whatsapp = false,
        public ?bool $database = false,
    ) {
    }

    public function toChannel(): array
    {
        return array_keys(array_filter($this->toArray()));
    }
}
