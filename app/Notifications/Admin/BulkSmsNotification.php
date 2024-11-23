<?php

namespace App\Notifications\Admin;

use App\Data\SmsData;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BulkSmsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $phone,
        public string $message
    ) {}

    public function via($notifiable): array
    {
        return [SmsNotificationChannel::class];
    }

    public function toSms($notifiable): SmsData
    {
        return new SmsData(
            phone: $this->phone,
            text: $this->message
        );
    }
}
