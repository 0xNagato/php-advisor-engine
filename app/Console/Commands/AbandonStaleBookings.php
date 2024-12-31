<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AbandonStaleBookings extends Command
{
    protected $signature = 'bookings:abandon-stale';

    protected $description = 'Abandon bookings that have been pending or guest_on_page for more than 30 minutes';

    public function handle()
    {
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);

        $staleBookings = Booking::query()
            ->with(['schedule', 'venue', 'concierge'])
            ->whereIn('status', [
                BookingStatus::PENDING,
                BookingStatus::GUEST_ON_PAGE,
            ])
            ->where('updated_at', '<', $thirtyMinutesAgo)
            ->get();

        $count = $staleBookings->count();

        if ($count === 0) {
            $this->info('No stale bookings found.');

            return;
        }

        foreach ($staleBookings as $booking) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'action' => 'auto_abandoned',
                    'previous_status' => $booking->status,
                    'booking_id' => $booking->id,
                ])
                ->log('Booking automatically abandoned due to inactivity');

            $booking->update(['status' => BookingStatus::ABANDONED]);
        }

        $this->info("Successfully abandoned {$count} stale bookings.");
    }
}
