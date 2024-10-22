<?php

namespace App\Notifications\Venue;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VenueContactLoginSMS extends Notification
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
    public function toSms(VenueContactData $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->contact_phone,
            templateKey: 'venue_contact_login',
            templateData: [
                'login_url' => $this->shortURL,
            ]
        );
    }
}
