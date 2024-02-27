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
            ->from('info@primavip.co')
            ->subject('Welcome to the Prima!')
            ->greeting('Welcome to the Prima!')
            ->line('You have been invited to the Prima!')
            ->action('Setup Password', $this->passwordResetUrl)
            ->line('If you did not expect to receive an invitation to the Prima, you may discard this email.');
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage|TwilioMessage
    {
        return (new TwilioSmsMessage())
            ->content("Welcome to the Prima! Setup your password at {$this->passwordResetUrl}");
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

    protected function passwordResetUrl(): string
    {
        $token = Password::createToken($this->user);

        return Filament::getResetPasswordUrl($token, $this->user);
    }
}
