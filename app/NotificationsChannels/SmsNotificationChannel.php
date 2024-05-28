<?php

namespace App\NotificationsChannels;

use App\Data\SmsData;
use App\Services\SmsService;
use Exception;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;

class SmsNotificationChannel
{
    /**
     * @throws Exception
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            throw new Exception('The notification must have a toSms method');
        }

        $data = $notification->toSms($notifiable);

        if (! $data instanceof SmsData) {
            throw new Exception('toSms should return SmsData');
        }

        $response = (new SmsService())->sendMessage(
            contactPhone: $data->phone,
            text: $data->text,
        );

        if ($response->failed()) {
            event(new NotificationFailed(
                $notifiable,
                $notification,
                'sms',
                [
                    'message' => $response->status(),
                    'exception' => $response->body(),
                ]
            ));
        }
    }
}
