<?php

namespace App\Jobs;

use App\Notifications\Admin\BulkSmsNotification;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly array $phoneNumbers,
        private readonly string $message
    ) {}

    public function handle(): void
    {
        foreach ($this->phoneNumbers as $phone) {
            app(SmsNotificationChannel::class)->send(
                notifiable: null,
                notification: new BulkSmsNotification($phone, $this->message)
            );
        }
    }
}
