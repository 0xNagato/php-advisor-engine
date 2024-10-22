<?php

namespace App\Notifications\User;

use App\Data\SmsData;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendTwoFactorCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $code
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
    }

    public function toSms(User $notifiable): SMSData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'two_factor_code',
            templateData: [
                'code' => $this->code,
            ]
        );
    }
}
