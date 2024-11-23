<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CancelStaleBookings extends Command
{
    protected $signature = 'bookings:cancel-stale';

    protected $description = 'Cancel bookings that have been pending or guest_on_page for more than 30 minutes';

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
                    'action' => 'auto_cancelled',
                    'previous_status' => $booking->status,
                    'booking_id' => $booking->id,
                ])
                ->log('Booking automatically cancelled due to inactivity');

            $booking->update(['status' => BookingStatus::CANCELLED]);
        }

        $this->info("Successfully cancelled {$count} stale bookings.");
    }
}
