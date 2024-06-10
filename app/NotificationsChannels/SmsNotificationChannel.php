<?php

namespace App\NotificationsChannels;

use App\Data\SmsData;
use App\Services\SmsService;
use Exception;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsNotificationChannel
{
    /**
     * @throws Exception
     */
    public function send($notifiable, Notification $notification): void
    {
        throw_unless(method_exists($notification, 'toSms'), new RuntimeException('The notification must have a toSms method'));
        throw_unless($notification->toSms($notifiable) instanceof SmsData, new Exception('toSms should return SmsData'));

        $data = $notification->toSms($notifiable);

        if(app()->isLocal()) {
            Log::info('[LOG] Sending SMS to '.$data->phone, [
                'text' => $data->text,
            ]);

            return;
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
