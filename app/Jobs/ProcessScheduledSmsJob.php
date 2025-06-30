<?php

namespace App\Jobs;

use App\Models\ScheduledSms;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $scheduledSmsId
    ) {
        // Using default queue
    }

    public function handle(): void
    {
        try {
            $scheduledSms = ScheduledSms::query()->find($this->scheduledSmsId);

            if (! $scheduledSms || $scheduledSms->status !== 'scheduled') {
                return;
            }

            // Mark as processing
            $scheduledSms->update(['status' => 'processing']);

            // Extract the recipients and message from the scheduled SMS
            $recipientData = $scheduledSms->recipient_data;
            $message = $scheduledSms->message;

            // Check if this is a test message
            $meta = $scheduledSms->meta ?? [];
            $testMode = $meta['test_mode'] ?? false;

            if ($testMode) {
                Log::info("TEST MODE: Processing test scheduled SMS #{$scheduledSms->id}");
            } else {
                Log::info("Processing scheduled SMS #{$scheduledSms->id} with {$scheduledSms->total_recipients} recipients");
            }

            foreach ($recipientData as $recipients) {
                $phoneNumbers = collect($recipients);

                // Process in chunks to avoid overloading the SMS service
                $phoneNumbers->chunk(50)->each(function ($chunk) use ($message) {
                    dispatch(new SendBulkSmsJob($chunk->toArray(), $message));
                });
            }

            // Mark as sent
            $scheduledSms->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (Exception $e) {
            // If there's a scheduled SMS record, mark it as failed
            if (isset($scheduledSms)) {
                $scheduledSms->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            Log::error('Failed to process scheduled SMS: '.$e->getMessage(), [
                'scheduled_sms_id' => $this->scheduledSmsId,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
