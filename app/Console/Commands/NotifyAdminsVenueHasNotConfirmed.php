<?php

namespace App\Console\Commands;

use App\Actions\Booking\NotifyAdminsOfUnconfirmedBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyAdminsVenueHasNotConfirmed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-admins-venue-has-not-confirmed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify admins about venue bookings that have not been confirmed by the venue';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Booking::query()
            ->where('status', BookingStatus::CONFIRMED)
            ->whereNull('venue_confirmed_at')
            ->whereNull('resent_venue_confirmation_at')
            ->where('booking_at', '=', now()->addMinutes(30))
            ->each(function ($booking) {
                $timezone = $booking->venue->timezone;
                $currentLocalTime = now($timezone);

                // Define the allowed operating window in the venue's timezone
                $allowedStart = Carbon::createFromFormat('H:i:s', '11:00:00', $timezone);
                $allowedEnd = Carbon::createFromFormat('H:i:s', '20:00:00', $timezone);

                // If current local time is within the allowed window, run the action
                if ($currentLocalTime->between($allowedStart, $allowedEnd)) {
                    NotifyAdminsOfUnconfirmedBooking::run($booking);
                }
            });
    }
}
