<?php

namespace App\Services\Booking;

use App\Constants\BookingPercentages;
use App\Models\Booking;
use App\Models\ScheduleTemplate;
use Illuminate\Support\Facades\Log;

readonly class NonPrimeEarningsCalculationService
{
    public function __construct(
        private EarningCreationService $earningCreationService
    ) {}

    public function calculate(Booking $booking): void
    {
        // Always use venue's default price for non-prime bookings if no specific override
        $pricePerHead = $booking->venue->non_prime_fee_per_head;

        // If we have a schedule template, check for price override
        if ($booking->schedule_template_id) {
            $scheduleTemplate = ScheduleTemplate::find($booking->schedule_template_id);
            if ($scheduleTemplate && $scheduleTemplate->price_per_head) {
                $pricePerHead = $scheduleTemplate->price_per_head;
            }
        }

        $fee = $pricePerHead * $booking->guest_count;
        $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
        $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
        $platform_venue = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $platform_earnings = $platform_concierge + $platform_venue;
        $venue_earnings = ($concierge_earnings + $platform_earnings) * -1;

        try {
            $this->createNonPrimeEarnings($booking, $venue_earnings, $concierge_earnings);

            $booking->update([
                'concierge_earnings' => $concierge_earnings * 100,
                'venue_earnings' => $venue_earnings * 100,
                'platform_earnings' => $platform_earnings * 100,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save earnings', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function createNonPrimeEarnings(Booking $booking, float $venue_earnings, float $concierge_earnings): void
    {
        try {
            $this->earningCreationService->createEarning(
                $booking,
                'venue_paid',
                $venue_earnings * 100,
                BookingPercentages::NON_PRIME_VENUE_PERCENTAGE,
                'concierge_bounty'
            );

            $this->earningCreationService->createEarning(
                $booking,
                'concierge_bounty',
                $concierge_earnings * 100,
                BookingPercentages::NON_PRIME_CONCIERGE_PERCENTAGE,
                'concierge_bounty'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create earnings records', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
