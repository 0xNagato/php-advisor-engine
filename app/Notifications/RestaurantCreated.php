<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

class RestaurantCreated extends Notification
{
    use Queueable;

    private string $passwordResetUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user)
    {
        $this->passwordResetUrl = $this->passwordResetUrl();
    }

    protected function passwordResetUrl(): string
    {
        $token = Password::createToken($this->user);

        return Filament::getResetPasswordUrl($token, $this->user);
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
            ->from('welcome@primavip.co')
            ->subject('Welcome to PRIMA!')
            ->markdown('mail.restaurant-welcome-mail', ['passwordResetUrl' => $this->passwordResetUrl]);
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage|TwilioMessage
    {
        return (new TwilioSmsMessage())
            ->content("Welcome to PRIMA! Your concierge account has been created. Please click {$this->passwordResetUrl()} to login and update your payment info and begin making reservations. Thank you for joining us!");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [

        ];
    }
}
