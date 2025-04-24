<?php

namespace App\Services\Booking;

use App\Enums\VenueType;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

readonly class BookingCalculationService
{
    public function __construct(
        private PrimeEarningsCalculationService $primeEarningsCalculationService,
        private NonPrimeEarningsCalculationService $nonPrimeEarningsCalculationService
    ) {}

    /**
     * @throws Throwable
     */
    public function calculateEarnings(Booking $booking): void
    {
        if (! $booking->relationLoaded('venue')) {
            $booking->load('venue');
        }

        if (! $booking->venue) {
            throw new RuntimeException("Booking ID {$booking->id} is missing venue relationship.");
        }

        DB::transaction(function () use ($booking) {
            $booking->earnings()->delete();

            if ($booking->is_prime) {
                $this->primeEarningsCalculationService->calculate($booking);
            } else {
                $this->nonPrimeEarningsCalculationService->calculate($booking);
            }
        });
    }

    public function calculateNonPrimeEarnings(Booking $booking): void
    {
        if (! $booking->relationLoaded('venue')) {
            $booking->load('venue');
        }

        if ($booking->venue && $booking->venue->venue_type === VenueType::HIKE_STATION) {
            \Log::warning("Attempted to calculate non-prime earnings for Hike Station Booking ID {$booking->id}. Skipping.");

            return;
        }

        $this->nonPrimeEarningsCalculationService->calculate($booking);
    }

    public function calculateRefundAmount(Booking $booking, string $refundType, ?int $guestCount = 0): int
    {
        if (! $booking->relationLoaded('venue')) {
            $booking->load('venue');
        }

        if ($booking->venue && $booking->venue->venue_type === VenueType::HIKE_STATION) {
            if ($refundType === 'full' || ! $guestCount || $guestCount >= $booking->guest_count) {
                return $booking->total_fee;
            }

            $remainingHikers = $booking->guest_count - $guestCount;
            if ($remainingHikers < 5) {
                \Log::warning("Partial refund for Hike Station Booking ID {$booking->id} requested dropping below 5 hikers. Recalculating refund based on remaining.");
                $remainingHikers = max(0, $remainingHikers);
            }

            $baseFeePerHiker = 600;
            $surchargePerExtraHiker = 6000;
            $minimumHikersForSurcharge = 5;

            $newExtraHikers = max(0, $remainingHikers - $minimumHikersForSurcharge);
            $newTotalFee = ($remainingHikers * $baseFeePerHiker) + ($newExtraHikers * $surchargePerExtraHiker);

            $refundAmount = $booking->total_fee - $newTotalFee;

            return max(0, $refundAmount);
        }

        if ($refundType === 'full' || ! $guestCount || $guestCount >= $booking->guest_count) {
            return $booking->total_with_tax_in_cents ?? $booking->total_fee;
        }

        $totalAmount = $booking->total_with_tax_in_cents ?? $booking->total_fee;
        if ($booking->guest_count <= 0) {
            \Log::error("Attempted refund calculation for Booking ID {$booking->id} with guest count {$booking->guest_count}");

            return 0;
        }
        $perGuestAmount = (int) floor($totalAmount / $booking->guest_count);

        return $perGuestAmount * $guestCount;
    }
}
