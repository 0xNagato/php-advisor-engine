<?php

namespace App\Data;

use App\NotificationsChannels\SmsNotificationChannel;
use App\NotificationsChannels\WhatsappNotificationChannel;
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
        return array_filter([
            $this->mail ? 'mail' : null,
            $this->sms ? SMSNotificationChannel::class : null,
            // $this->whatsapp ? WhatsAppNotificationChannel::class : null,
            $this->database ? 'database' : null,
        ]);
    }
}
