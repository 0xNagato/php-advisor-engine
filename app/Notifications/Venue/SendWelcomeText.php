<?php

namespace App\Notifications\Venue;

use App\Data\SmsData;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendWelcomeText extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
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

    /**
     * @throws ShortURLException
     */
    public function toSms(User $notifiable): SMSData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'venue_welcome',
            templateData: []
        );
    }
}
