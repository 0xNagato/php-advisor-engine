<?php

namespace App\Notifications;

use App\Data\PushNotificationData;
use App\NotificationsChannels\ExpoPushNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamplePushNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected array $data = [],
    ) {}

    public function via($notifiable): array
    {
        return [ExpoPushNotificationChannel::class];
    }

    public function toPush($notifiable): PushNotificationData
    {
        return new PushNotificationData(
            title: $this->title,
            body: $this->message,
            data: $this->data,
        );
    }
}
