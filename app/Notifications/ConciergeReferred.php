<?php

namespace App\Notifications;

use App\Models\User;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

class ConciergeReferred extends Notification
{
    use Queueable;

    protected string $passwordResetUrl;

    /**
     * Create a new notification instance.
     *
     * @throws ShortURLException
     */
    public function __construct(public User $user)
    {
        $this->passwordResetUrl = $this->passwordResetUrl();
    }

    /**
     * @throws ShortURLException
     */
    protected function passwordResetUrl(): string
    {
        $token = Password::createToken($this->user);
        $url = Filament::getResetPasswordUrl($token, $this->user);

        return ShortURL::destinationUrl($url)->make()->default_short_url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', TwilioChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->from('welcome@primavip.co', 'PRIMA')
            ->subject('Welcome to PRIMA!')
            ->markdown('mail.concierge-welcome-mail', ['passwordResetUrl' => $this->passwordResetUrl]);
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage|TwilioMessage
    {
        $name = 'PRIMA';

        return (new TwilioSmsMessage())
            ->content("You've been invited to PRIMA by $name. Please click $this->passwordResetUrl to create your profile and start earning! Welcome aboard!");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [

        ];
    }
}
