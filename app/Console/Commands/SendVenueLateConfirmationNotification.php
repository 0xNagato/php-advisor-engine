<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\VenueBookingLateConfirmationService;
use Illuminate\Console\Command;

class SendVenueLateConfirmationNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-venue-late-confirmation-notification';

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
        Booking::query()->whereNull('venue_confirmed_at')
            ->whereNull('resent_venue_confirmation_at')
            ->where('confirmed_at', '<=', now()->subMinutes(15))
            ->each(function ($booking) {
                app(VenueBookingLateConfirmationService::class)->sendNotification($booking);
            });
    }
}
