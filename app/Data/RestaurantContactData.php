<?php

namespace App\Data;

use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Notifications\AnonymousNotifiable;
use Spatie\LaravelData\Data;

class RestaurantContactData extends Data
{
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

    public function toNotifiable(): AnonymousNotifiable
    {
        return (new AnonymousNotifiable)->route(SmsNotificationChannel::class, $this->contact_phone);
    }

    public function toChannel(): array
    {
        return $this->preferences?->toChannel() ?? [];
    }
}
