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

class ConciergeCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user)
    {
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
        return (new MailMessage)
            ->greeting('Welcome to the Concierge App!')
            ->line('Welcome to the Concierge App!')
            ->action('Setup Password', $this->passwordResetUrl())
            ->line('If you did not expect to receive an invitation to the Concierge App, you may discard this email.');
    }

    protected function passwordResetUrl(): string
    {
        $token = Password::createToken($this->user);

        return Filament::getResetPasswordUrl($token, $this->user);
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage|TwilioMessage
    {
        return (new TwilioSmsMessage())
            ->content("Welcome to the Concierge App! Setup your password at {$this->passwordResetUrl()}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
