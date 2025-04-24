<?php

namespace App\Services\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($booking) {
            if ($booking->is_prime) {
                $this->primeEarningsCalculationService->calculate($booking);
            } else {
                $this->nonPrimeEarningsCalculationService->calculate($booking);
            }
        });
    }

    public function calculateNonPrimeEarnings(Booking $booking): void
    {
        $this->nonPrimeEarningsCalculationService->calculate($booking);
    }

    public function calculateRefundAmount(Booking $booking, string $refundType, ?int $guestCount = 0): int
    {
        if ($refundType === 'full' || ! $guestCount || $guestCount === $booking->guest_count) {
            return $booking->total_with_tax_in_cents;
        }

        // Calculate per guest amount and ensure we get exact division
        $perGuestAmount = (int) ($booking->total_with_tax_in_cents / $booking->guest_count);

        return $perGuestAmount * $guestCount;
    }
}
