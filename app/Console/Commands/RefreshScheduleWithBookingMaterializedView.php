<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshScheduleWithBookingMaterializedView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-schedule-with-booking-materialized-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the materialized view schedule_with_bookings_mv in PostgreSQL database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Validate if the current database connection is pgsql
            $currentConnection = config('database.default');
            if ($currentConnection !== 'pgsql') {
                $this->error("This command will not run, because the current connection is not 'pgsql'. ");

                return Command::FAILURE;
            }

            // Start performance tracking
            $startTime = now();

            // Execute the PostgreSQL query to refresh the materialized view
            DB::connection('pgsql')->statement('REFRESH MATERIALIZED VIEW schedule_with_bookings_mv;');

            // Calculate time elapsed
            $executionTime = $startTime->diffInMilliseconds(now());

            // Output success message and performance stats
            $this->info('The materialized view schedule_with_bookings_mv has been refreshed successfully.');
            $this->info("Query executed in {$executionTime} ms.");

            // Log the performance data
            logger()->info('Materialized view refresh completed.', [
                'execution_time_ms' => $executionTime,
            ]);

            return Command::SUCCESS;
        } catch (Exception $e) {
            // Handle any errors during the query execution
            $this->error('Error refreshing the materialized view: '.$e->getMessage());

            // Log the error for further analysis
            logger()->error('Error refreshing materialized view.', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
