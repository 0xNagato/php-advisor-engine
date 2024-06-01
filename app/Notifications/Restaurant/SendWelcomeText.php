<?php

namespace App\Notifications\Restaurant;

use App\Data\SmsData;
use App\Models\Restaurant;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

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

    public function toSms(Restaurant $notifiable): SMSData
    {
        $token = Password::createToken($notifiable->user);
        $url = Filament::getResetPasswordUrl($token, $notifiable->user);

        $secureUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        return new SmsData(
            phone: $notifiable->user->phone,
            text: '❤️ Thank you for joining PRIMA! Our concierge team is currently being onboarded and will start generating reservations soon! We will notify you via text as soon as we are ready to launch! With gratitude, Team PRIMA.'
        );
    }
}
