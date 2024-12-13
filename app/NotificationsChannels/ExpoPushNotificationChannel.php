<?php

namespace App\NotificationsChannels;

use App\Data\PushNotificationData;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExpoPushNotificationChannel
{
    protected string $expoEndpoint = 'https://exp.host/--/api/v2/push/send';

    public function send($notifiable, Notification $notification): void
    {
        throw_unless(method_exists($notification, 'toPush'), new RuntimeException('Notification must implement toPush method'));

        throw_unless($notification->toPush($notifiable) instanceof PushNotificationData, new RuntimeException('toPush should return PushNotificationData'));

        $data = $notification->toPush($notifiable);

        if (blank($notifiable->expo_push_token)) {
            return;
        }

        Http::post($this->expoEndpoint, [
            'to' => $notifiable->expo_push_token,
            'title' => $data->title,
            'body' => $data->body,
            'data' => $data->data,
            'sound' => 'default',
            'priority' => 'high',
        ]);
    }
}
