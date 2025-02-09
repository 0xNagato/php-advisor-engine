<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTaskCreated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $taskName,
        public readonly string $taskNotes,
        public readonly string $taskUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('tasks@primavip.co', 'PRIMA Task System')
            ->subject('New Task Created: '.$this->taskName)
            ->greeting('Hello '.$notifiable->first_name)
            ->line('A new task has been created in Asana based on your request.')
            ->line('Task Details:')
            ->line("Name: {$this->taskName}")
            ->line("Description: {$this->taskNotes}")
            ->action('View Task in Asana', $this->taskUrl)
            ->line('Thank you for using PRIMA!');
    }
}
