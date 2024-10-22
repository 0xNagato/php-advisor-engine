<?php

namespace App\Notifications;

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

class PartnerCreated extends Notification
{
    use Queueable;

    private string $passwordResetUrl;

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
        return ['mail', SmsNotificationChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('welcome@primavip.co', 'PRIMA')
            ->subject('Welcome to PRIMA!')
            ->markdown('mail.partner-welcome-mail', ['passwordResetUrl' => $this->passwordResetUrl]);
    }

    public function toSms(object $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'partner_created',
            templateData: [
                'login_url' => $this->passwordResetUrl,
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
