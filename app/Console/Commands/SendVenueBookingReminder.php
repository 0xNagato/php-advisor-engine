<?php

namespace App\Console\Commands;

use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Enums\BookingStatus;
use App\Models\Booking;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendVenueBookingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-venue-booking-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a reminder SMS for bookings that have not been confirmed by the venue';

    /**
     * Execute the console command.
     *
     * @throws ShortURLException
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

                $allowedStart = Carbon::createFromFormat('H:i:s', '11:00:00', $timezone);
                $allowedEnd = Carbon::createFromFormat('H:i:s', '20:00:00', $timezone);

                if ($currentLocalTime->between($allowedStart, $allowedEnd)) {
                    SendConfirmationToVenueContacts::run(booking: $booking, reminder: true);
                    $booking->update(['resent_venue_confirmation_at' => now()]);
                }
            });
    }
}
