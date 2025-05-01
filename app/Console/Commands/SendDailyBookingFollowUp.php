<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingCustomerReminderLog;
use App\Notifications\Booking\CustomerFollowUp;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SendDailyBookingFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prima:bookings-send-daily-customer-follow-up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS follow up to customers with confirmed bookings from the previous day.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $yesterday = Carbon::yesterday();
        $today = Carbon::today();

        // Query confirmed bookings from yesterday
        Booking::query()->whereDate('booking_at', $yesterday)
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->whereNotNull('guest_phone')
            ->whereNotIn('guest_phone', function ($query) use ($today) {
                $query->select('guest_phone')
                    ->from('bookings')->whereNotNull('guest_phone')
                    ->whereDate('booking_at', $today);
            })
            ->whereDoesntHave('reminderLogs', function (Builder $query) {
                $query->whereColumn('booking_id', 'bookings.id');
            })
            ->with(['venue'])
            ->each(function ($booking) {
                $timezone = $booking->venue->timezone;
                $currentLocalTime = now($timezone);

                $allowedStart = Carbon::createFromFormat('H:i:s', '11:59:00', $timezone);
                $allowedEnd = Carbon::createFromFormat('H:i:s', '12:01:00', $timezone);

                if ($currentLocalTime->between($allowedStart, $allowedEnd)) {
                    // Dispatch job to send SMS for each eligible booking
                    $booking->notify(new CustomerFollowUp($booking));

                    // Log the reminder in the database
                    BookingCustomerReminderLog::query()->create([
                        'booking_id' => $booking->id,
                        'guest_phone' => $booking->guest_phone,
                        'sent_at' => now(),
                    ]);

                    $this->info("SMS follow up sent to {$booking->guest_phone} for booking ID {$booking->id}.");
                }
            });

        $this->info('Daily booking follow up SMS sent.');
    }
}
