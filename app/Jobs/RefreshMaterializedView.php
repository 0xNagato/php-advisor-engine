<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class RefreshMaterializedView implements ShouldQueue
{
    use Queueable;

    /**
     * Set the maximum number of attempts to retry the job.
     */
    public int $tries = 3;

    /**
     * Maximum time (in seconds) for the job to run.
     */
    public int $timeout = 300;

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            // Trigger Artisan command
            Artisan::call('app:refresh-schedule-with-booking-materialized-view');
        } catch (Exception $e) {
            // Log if all retries fail
            logger()->error('Failed refreshing materialized view: '.$e->getMessage());
        }
    }
}
