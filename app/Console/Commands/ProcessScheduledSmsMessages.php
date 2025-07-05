<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledSmsJob;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledSmsMessages extends Command
{
    protected $signature = 'sms:process-scheduled';

    protected $description = 'Process any scheduled SMS messages that are due to be sent';

    public function handle(): int
    {
        $this->info('Checking for scheduled SMS messages to process...');

        // Current time in UTC for comparison with scheduled_at_utc
        $currentUtcTime = now()->setTimezone('UTC');

        // Find all scheduled SMS messages that are due to be sent
        $scheduledMessages = SmsMessage::query()->where('status', 'scheduled')
            ->where('scheduled_at_utc', '<=', $currentUtcTime)
            ->get();

        // Log for verification purposes
        $this->info('Current UTC time: '.$currentUtcTime->toDateTimeString());

        $count = $scheduledMessages->count();

        if ($count === 0) {
            $this->info('No scheduled SMS messages to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$count} scheduled SMS messages...");

        foreach ($scheduledMessages as $scheduledSms) {
            $this->info("Dispatching job to process scheduled SMS #{$scheduledSms->id} with {$scheduledSms->total_recipients} recipients");

            try {
                // Dispatch a job to process each scheduled SMS
                ProcessScheduledSmsJob::dispatch($scheduledSms->id);
            } catch (Exception $e) {
                Log::error("Failed to dispatch job for scheduled SMS #{$scheduledSms->id}: ".$e->getMessage());
                $this->error("Failed to dispatch job for scheduled SMS #{$scheduledSms->id}: ".$e->getMessage());
            }
        }

        $this->info('All scheduled SMS messages have been dispatched for processing.');

        return self::SUCCESS;
    }
}
