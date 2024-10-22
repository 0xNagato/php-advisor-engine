<?php

namespace App\Notifications\Concierge;

use App\Data\SmsData;
use App\Models\Referral;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InvitationReminder extends Notification
{
    use Queueable;

    protected string $shortURL;

    /**
     * Create a new notification instance.
     *
     * @throws ShortURLException
     */
    public function __construct(public Referral $referral)
    {
        $url = URL::temporarySignedRoute('concierge.invitation', now()->addDays(15), [
            'referral' => $this->referral,
        ]);

        $this->shortURL = ShortURL::destinationUrl($url)->make()->default_short_url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [SmsNotificationChannel::class];
    }

    public function toSms(Referral $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'concierge_reminder',
            templateData: [
                'first_name' => $this->referral->first_name,
                'referrer' => $this->referral->referrer->name,
                'url' => $this->shortURL,
            ]
        );
    }
}
