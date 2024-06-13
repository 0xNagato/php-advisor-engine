<?php

namespace App\Data;

use Illuminate\Notifications\Notifiable;
use Spatie\LaravelData\Data;

class RestaurantContactData extends Data
{
    use Notifiable;

    public function __construct(
        public string $contact_name,
        public string $contact_phone,
        public bool $use_for_reservations,
        public ?NotificationPreferencesData $preferences = null,
    ) {
        $this->preferences = NotificationPreferencesData::from([
            'mail' => false,
            'sms' => true,
            'whatsapp' => false,
            'database' => false,
        ]);
    }

    public function toChannel(): array
    {
        return $this->preferences?->toChannel() ?? [];
    }
}
