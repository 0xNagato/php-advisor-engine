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
        $fee = $booking->venue->non_prime_fee_per_head * $booking->guest_count;
        $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
        $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
        $platform_venue = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_Venue / 100);
        $platform_earnings = $platform_concierge + $platform_venue;
        $venue_earnings = ($concierge_earnings + $platform_earnings) * -1;

        $this->createNonPrimeEarnings($booking, $venue_earnings, $concierge_earnings);

        $booking->update([
            'concierge_earnings' => $concierge_earnings * 100,
            'venue_earnings' => $venue_earnings * 100,
            'platform_earnings' => $platform_earnings * 100,
        ]);
    }

    private function createNonPrimeEarnings(Booking $booking, float $venue_earnings, float $concierge_earnings): void
    {
        $this->earningCreationService->createEarning(
            $booking,
            'venue_paid',
            $venue_earnings * 100,
            BookingPercentages::NON_PRIME_Venue_PERCENTAGE,
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
