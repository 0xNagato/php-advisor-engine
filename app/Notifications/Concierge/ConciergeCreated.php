<?php

namespace App\Notifications\Concierge;

use App\Data\SmsData;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class ConciergeCreated extends Notification
{
    use Queueable;

    protected string $passwordResetUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user)
    {
        $this->passwordResetUrl = $this->passwordResetUrl();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            'mail',
            SmsNotificationChannel::class,
        ];
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

    public function toSMS(object $notifiable): SmsData
    {
        return new SmsData(
            phone: $this->user->phone,
            text: "Welcome to PRIMA! Your account has been created. Please click $this->passwordResetUrl to login and update your payment info and begin making reservations. Thank you for joining us!"
        );
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
}
