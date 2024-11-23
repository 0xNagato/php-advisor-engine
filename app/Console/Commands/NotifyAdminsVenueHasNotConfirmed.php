<?php

namespace App\Console\Commands;

use App\Actions\Booking\NotifyAdminsOfUnconfirmedBooking;
use App\Models\Booking;
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
            ->whereNull('venue_confirmed_at')
            ->whereNull('resent_venue_confirmation_at')
            ->where('booking_at', '=', now()->addMinutes(30))
            ->each(function ($booking) {
                NotifyAdminsOfUnconfirmedBooking::run($booking);
            });
    }
}
