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
        throw_unless(method_exists($notification, 'toSms'), new Exception('The notification must have a toSms method'));

        $data = $notification->toSms($notifiable);

        throw_unless($data instanceof SmsData, new Exception('toSms should return SmsData'));

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
