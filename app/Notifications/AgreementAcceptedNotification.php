<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgreementAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $companyName)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Agreement Accepted')
            ->line("The agreement for the company \"{$this->companyName}\" has been accepted.")
            ->line('You can view more details in the admin panel.');
    }
}
