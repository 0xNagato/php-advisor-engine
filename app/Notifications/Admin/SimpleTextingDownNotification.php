<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SimpleTextingDownNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $error,
        public string $recipientPhone,
        public string $messageText
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('SimpleTexting API is Down')
            ->greeting('SimpleTexting API Failure')
            ->line('The SimpleTexting API has failed to send a message.')
            ->line('**Error Details:**')
            ->line("Error: {$this->error}")
            ->line("Recipient Phone: {$this->recipientPhone}")
            ->line('**Message that failed to send:**')
            ->line($this->messageText)
            ->line('The system will automatically retry sending this message.')
            ->line('Please investigate the issue and ensure the SimpleTexting service is operational.');
    }
}
