<?php

namespace App\Notifications\Concierge;

use App\Data\SmsData;
use App\Models\Referral;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use App\Services\PrimaShortUrls;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NotifyConciergeReferral extends Notification
{
    use Queueable;

    protected string $shortURL;

    protected User $referrer;

    /**
     * @throws ShortURLException
     */
    public function __construct(public Referral $referral, public string $channel = 'sms')
    {
        $this->referrer = $this->referral->referrer;

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
        return $this->channel === 'sms' ? [SmsNotificationChannel::class] : ['mail'];
    }

    public function toSms(Referral $notifiable): SMSData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'concierge_referral',
            templateData: [
                'first_name' => $notifiable->first_name,
                'referrer' => $this->referrer->name,
                'url' => $this->shortURL,
                'how_it_works' => PrimaShortUrls::get('how-it-works'),
            ]
        );
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('welcome@primavip.co', 'PRIMA')
            ->subject('Welcome to PRIMA!')
            ->markdown('mail.concierge-referral-mail', ['passwordResetUrl' => $this->shortURL, 'referrer' => $this->referrer->name]);
    }
}
