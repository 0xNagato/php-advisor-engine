<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SimpleTextingFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $phone,
        private readonly int $status,
        private readonly array $body,
        private readonly int $attempt,
        private readonly string $text
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SimpleTexting Failed - {$this->phone}")
            ->line("Phone Number: {$this->phone}")
            ->line("HTTP Status: {$this->status}")
            ->line("Attempt Number: {$this->attempt}")
            ->line("Message Text: {$this->text}")
            ->line('Response Body:')
            ->line(json_encode($this->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
