<?php

namespace App\NotificationsChannels;

use App\Data\SmsData;
use App\Http\Integrations\Twilio\Twilio;
use Exception;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use RuntimeException;

class WhatsappNotificationChannel
{
    /**
     * @throws Exception
     */
    public function send($notifiable, Notification $notification): void
    {
        throw_unless(method_exists($notification, 'toWhatsapp'), new RuntimeException('The notification must have a toWhatsapp method'));
        throw_unless($notification->toWhatsapp($notifiable) instanceof SmsData, new Exception('toWhatsapp should return SmsData'));

        $data = $notification->toWhatsapp($notifiable);

        $response = (new Twilio)->whatsapp(
            phone: $data->phone,
            text: $data->text,
        );

        if ($response->failed()) {
            event(new NotificationFailed(
                $notifiable,
                $notification,
                'whatsapp',
                [
                    'message' => $response->status(),
                    'exception' => $response->body(),
                ]
            ));
        }
    }
}
