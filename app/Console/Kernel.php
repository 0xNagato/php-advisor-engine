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
        $schedule->command('telescope:prune --hours=48')->daily();

        // Check for scheduled SMS messages every minute
        $schedule->command('sms:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping();

        /**
         * Production-only scheduled tasks
         */
        if (config('app.url') === self::PRODUCTION_URL) {
            // Send daily concierge invitation reminders at noon EST
            // $schedule->command('app:send-concierge-invitation-reminders')
            //     ->dailyAt('12:00')
            //     ->timezone('America/New_York');

            // Check every minute for bookings happening in 30 minutes and send reminders to venues if unconfirmed
            $schedule->command('app:send-venue-booking-reminder')
                ->everyMinute()
                ->withoutOverlapping();

            // Check every minute for bookings happening in 30 minutes and notify admins if venue hasn't confirmed
            // $schedule->command('app:notify-admins-venue-has-not-confirmed')
            //     ->everyMinute()
            //     ->withoutOverlapping();

            // Abandon stale bookings that have been pending for too long
            $schedule->command('bookings:abandon-stale')
                ->everyMinute()
                ->withoutOverlapping();

            // Send booking reminders
            $schedule->command('prima:bookings-send-customer-reminder')
                ->everyMinute()
                ->withoutOverlapping();

            // Send booking reminders
            $schedule->command('prima:bookings-send-daily-customer-follow-up')
                ->hourly()
                ->withoutOverlapping();

            // Sync QR code visit statistics
            $schedule->command('qr-codes:sync-stats')
                ->hourly()
                ->withoutOverlapping();

            // $schedule->command('app:send-daily-summary-email')->dailyAt('08:00')->timezone('America/New_York');
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
