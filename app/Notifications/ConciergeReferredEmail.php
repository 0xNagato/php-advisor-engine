<?php

namespace App\Notifications;

use App\Models\ConciergeReferral;
use App\Models\User;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConciergeReferredEmail extends Notification
{
    use Queueable;

    protected string $shortURL;

    protected User $referrer;

    /**
     * @throws ShortURLException
     */
    public function __construct(public ConciergeReferral $conciergeReferral)
    {
        $this->referrer = $this->conciergeReferral->concierge->user;

        $url = URL::temporarySignedRoute("concierge.invitation", now()->addDays(), [
            "conciergeReferral" => $this->conciergeReferral
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->from('welcome@primavip.co', 'PRIMA')
            ->subject('Welcome to PRIMA!')
            ->markdown('mail.concierge-referral-mail', ['passwordResetUrl' => $this->shortURL, 'referrer' => $this->referrer->name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
