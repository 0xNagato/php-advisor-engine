<?php

namespace App\Jobs;

use App\Http\Integrations\SimpleTexting\SimpleTexting;
use App\Models\User;
use App\Notifications\Admin\SimpleTextingDownNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSimpleTextingSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300; // 5 minutes

    public function __construct(
        private readonly string $phone,
        private readonly string $text
    ) {}

    public function handle(): void
    {
        $response = (new SimpleTexting)->sms(
            phone: $this->phone,
            text: $this->text
        );

        if ($response->failed()) {
            Log::error('SimpleTexting API failed', [
                'phone' => $this->phone,
                'text' => $this->text,
                'status' => $response->status(),
                'body' => $response->body(),
                'attempt' => $this->attempts(),
            ]);

            // If this was the last attempt, notify admins
            if ($this->attempts() === $this->tries) {
                User::role('super_admin')->each(function ($admin) use ($response) {
                    $admin->notify(new SimpleTextingDownNotification(
                        error: $response->body(),
                        recipientPhone: $this->phone,
                        messageText: $this->text
                    ));
                });
            }

            // Throw exception to trigger retry
            $this->fail($response->toException());
        }
    }
}
