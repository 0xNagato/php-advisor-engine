<?php

namespace App\Notifications\Restaurant;

use App\Data\RestaurantContactData;
use App\Data\SmsData;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RestaurantContactLoginSMS extends Notification
{
    use Queueable;

    public string $shortURL;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $url)
    {
        $this->shortURL = ShortURL::destinationUrl($this->url)->make()->default_short_url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return string
     */
    public function via(object $notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toSms(RestaurantContactData $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->contact_phone,
            text: 'Click the link to login: '.$this->shortURL
        );
    }
}
