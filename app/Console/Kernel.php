<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    public const string PRODUCTION_URL = 'https://primavip.co';

    public const string DEMO_URL = 'https://demo.primavip.co';

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * Send reminders for bookings that are due.
         */
        if (config('app.url') === self::PRODUCTION_URL) {
            $schedule->command('app:send-venue-booking-reminder')->everyFiveMinutes();
            $schedule->command('app:send-venue-late-confirmation-notification')->everyFiveMinutes();
        }

        /**
         * Generate demo bookings for the demo instance.
         */
        if (config('app.url') === self::DEMO_URL) {
            $schedule->command('demo:generate-bookings')->daily();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
