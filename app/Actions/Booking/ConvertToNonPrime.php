<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Services\Booking\NonPrimeEarningsCalculationService;
use App\Services\BookingService;
use Exception;
use Lorisleiva\Actions\Concerns\AsAction;

class ConvertToNonPrime
{
    use AsAction;

    public function handle(Booking $booking): array
    {
        try {
            // Convert to Non Prime, update fields
            app(BookingService::class)->convertToNonPrime($booking);

            // Apply Non Prime Earnings calculation
            app(NonPrimeEarningsCalculationService::class)->calculate($booking);

            // Log activity
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'guest_name' => $booking->guest_name,
                    'venue_name' => $booking->venue->name,
                    'booking_time' => $booking->booking_at->format('M d, Y h:i A'),
                    'guest_count' => $booking->guest_count,
                    'amount' => $booking->total_with_tax_in_cents,
                    'currency' => $booking->currency,
                ])
                ->log('Prime Booking converted to Non Prime');

            return [
                'success' => true,
                'message' => 'The booking has been successfully converted to Non Prime.',
            ];
        } catch (Exception $e) {
            logger()->error($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
