<?php

namespace App\Data;

use Illuminate\Notifications\Notifiable;
use Spatie\LaravelData\Data;

class VenueContactData extends Data
{
    use Notifiable;

    public function __construct(
        public string $contact_name,
        public ?string $contact_phone,
        public bool $use_for_reservations,
        public ?string $email = '',
        public ?NotificationPreferencesData $preferences = null,
    ) {}

    public function toChannel(): array
    {
        return $this->preferences?->toChannel() ?? [];
    }

    public function getKey(): string
    {
        return $this->contact_phone ?? $this->email;
    }
}
