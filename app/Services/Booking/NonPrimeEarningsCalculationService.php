<?php

namespace App\Services\Booking;

use App\Constants\BookingPercentages;
use App\Models\Booking;

readonly class NonPrimeEarningsCalculationService
{
    public function __construct(
        private EarningCreationService $earningCreationService
    ) {}

    public function calculate(Booking $booking): void
    {
        $fee = $booking->restaurant->non_prime_fee_per_head * $booking->guest_count;
        $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
        $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
        $platform_restaurant = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_RESTAURANT / 100);
        $platform_earnings = $platform_concierge + $platform_restaurant;
        $restaurant_earnings = ($concierge_earnings + $platform_earnings) * -1;

        $this->createNonPrimeEarnings($booking, $restaurant_earnings, $concierge_earnings);

        $booking->update([
            'concierge_earnings' => $concierge_earnings * 100,
            'restaurant_earnings' => $restaurant_earnings * 100,
            'platform_earnings' => $platform_earnings * 100,
        ]);
    }

    private function createNonPrimeEarnings(Booking $booking, float $restaurant_earnings, float $concierge_earnings): void
    {
        $this->earningCreationService->createEarning(
            $booking,
            'restaurant_paid',
            $restaurant_earnings * 100,
            BookingPercentages::NON_PRIME_RESTAURANT_PERCENTAGE,
            'concierge_bounty'
        );

        $this->earningCreationService->createEarning(
            $booking,
            'concierge_bounty',
            $concierge_earnings * 100,
            BookingPercentages::NON_PRIME_CONCIERGE_PERCENTAGE,
            'concierge_bounty'
        );
    }
}
