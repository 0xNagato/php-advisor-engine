<?php

namespace App\Services\Booking;

use App\Constants\BookingPercentages;
use App\Models\Booking;
use App\Models\Partner;
use Illuminate\Support\Facades\Log;

readonly class PrimeEarningsCalculationService
{
    public function __construct(
        private EarningCreationService $earningCreationService
    ) {}

    public function calculate(Booking $booking): void
    {
        $restaurant_earnings = $this->calculateRestaurantEarnings($booking);
        $concierge_earnings = $this->calculateConciergeEarnings($booking);

        $this->earningCreationService->createEarning($booking, 'restaurant', $restaurant_earnings, $booking->restaurant->payout_restaurant, 'total_fee');
        $this->earningCreationService->createEarning($booking, 'concierge', $concierge_earnings, $booking->concierge->payout_percentage, 'total_fee');

        $remainder = $booking->total_fee - $restaurant_earnings - $concierge_earnings;

        $remainder -= $this->calculateAndCreateReferralEarnings($booking, $remainder);
        $remainder -= $this->calculateAndCreatePartnerEarnings($booking, $remainder);

        $booking->restaurant_earnings = $restaurant_earnings;
        $booking->concierge_earnings = $concierge_earnings;
        $booking->platform_earnings = $remainder;
        $booking->save();

        Log::info('Prime earnings calculated', [
            'booking_id' => $booking->id,
            'restaurant_earnings' => $restaurant_earnings,
            'concierge_earnings' => $concierge_earnings,
            'platform_earnings' => $remainder,
        ]);
    }

    private function calculateRestaurantEarnings(Booking $booking): float
    {
        return $booking->total_fee * ($booking->restaurant->payout_restaurant / 100);
    }

    private function calculateConciergeEarnings(Booking $booking): float
    {
        return $booking->total_fee * ($booking->concierge->payout_percentage / 100);
    }

    private function calculateAndCreateReferralEarnings(Booking $booking, float $remainder): float
    {
        $totalReferralEarnings = 0;

        if ($booking->concierge->referringConcierge) {
            $amount = $remainder * (BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE / 100);
            $this->earningCreationService->createEarning($booking, 'concierge_referral_1', $amount, BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE, 'platform');
            $totalReferralEarnings += $amount;
        }

        if ($booking->concierge->referringConcierge?->referringConcierge) {
            $amount = $remainder * (BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE / 100);
            $this->earningCreationService->createEarning($booking, 'concierge_referral_2', $amount, BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE, 'platform');
            $totalReferralEarnings += $amount;
        }

        return $totalReferralEarnings;
    }

    private function calculateAndCreatePartnerEarnings(Booking $booking, float $remainder): float
    {
        $totalPartnerEarnings = 0;

        if ($booking->concierge->user->partner_referral_id) {
            $partner = Partner::query()->find($booking->concierge->user->partner_referral_id);
            $amount = $remainder * ($partner->percentage / 100);
            $this->earningCreationService->createEarning($booking, 'partner_concierge', $amount, $partner->percentage, 'remainder');
            $totalPartnerEarnings += $amount;
            $booking->partner_concierge_id = $partner->id;
            $booking->partner_concierge_fee = $amount;
        }

        if ($booking->restaurant->user->partner_referral_id) {
            $partner = Partner::query()->find($booking->restaurant->user->partner_referral_id);
            $amount = $remainder * ($partner->percentage / 100);
            $this->earningCreationService->createEarning($booking, 'partner_restaurant', $amount, $partner->percentage, 'remainder');
            $totalPartnerEarnings += $amount;
            $booking->partner_restaurant_id = $partner->id;
            $booking->partner_restaurant_fee = $amount;
        }

        return $totalPartnerEarnings;
    }
}
