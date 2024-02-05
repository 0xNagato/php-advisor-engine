<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

class ConciergeCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
    public function toMail(string $passwordResetUrl): MailMessage
    {
        return (new MailMessage)
            ->line('Welcome to the Concierge App!')
            ->action('Setup Password', $passwordResetUrl)
            ->line('If you did not expect to receive an invitation to the Concierge App, you may discard this email.');
    }

    public function toTwilio(string $passwordResetUrl): TwilioSmsMessage|TwilioMessage
    {
        return (new TwilioSmsMessage())
            ->content("Welcome to the Concierge App! Setup your password at {$passwordResetUrl}");
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
