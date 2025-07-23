<?php

namespace App\Services\Booking;

use App\Constants\BookingPercentages;
use App\Enums\EarningType;
use App\Models\Booking;
use App\Models\Earning;
use App\Models\Partner;

readonly class PrimeEarningsCalculationService
{
    public function __construct(
        private EarningCreationService $earningCreationService,
        private ConciergePromotionalEarningsService $promotionalEarningsService
    ) {}

    public function calculate(Booking $booking): void
    {
        $venue_earnings = $this->calculateVenueEarnings($booking);
        $concierge_earnings = $this->calculateConciergeEarnings($booking);

        $this->earningCreationService->createEarning(
            booking: $booking,
            type: EarningType::VENUE->value,
            amount: $venue_earnings,
            percentage: $booking->venue->payout_venue,
            percentageOf: 'total_fee'
        );
        $this->earningCreationService->createEarning(
            booking: $booking,
            type: EarningType::CONCIERGE->value,
            amount: $concierge_earnings,
            percentage: $booking->concierge->payout_percentage,
            percentageOf: 'total_fee'
        );

        $remainder = $booking->total_fee - $venue_earnings - $concierge_earnings;

        $remainder -= $this->calculateAndCreateReferralEarnings($booking, $remainder);
        $remainder -= $this->calculateAndCreatePartnerEarnings($booking, $remainder);

        $booking->venue_earnings = floor($venue_earnings);
        $booking->concierge_earnings = floor($concierge_earnings);
        $booking->platform_earnings = floor($remainder);
        $booking->save();
    }

    private function calculateVenueEarnings(Booking $booking): float
    {
        return $booking->total_fee * ($booking->venue->payout_venue / 100);
    }

    private function calculateConciergeEarnings(Booking $booking): float
    {
        // Check if this is a QR concierge and use their custom percentage
        if ($booking->concierge->is_qr_concierge) {
            return $booking->total_fee * ($booking->concierge->revenue_percentage / 100);
        }

        // Calculate base earnings for regular concierges
        $baseEarnings = 0;
        if ($booking->venue->is_omakase && $booking->venue->omakase_concierge_fee) {
            $baseEarnings = $booking->venue->omakase_concierge_fee * $booking->guest_count;
        } else {
            $baseEarnings = $booking->total_fee * ($booking->concierge->payout_percentage / 100);
        }

        // Apply promotional multiplier if applicable
        return $this->promotionalEarningsService->applyEarningsMultiplier($baseEarnings, $booking);
    }

    private function calculateAndCreateReferralEarnings(Booking $booking, float $remainder): float
    {
        $totalReferralEarnings = 0;

        if ($booking->concierge->referringConcierge) {
            $amount = $remainder * (BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE / 100);
            $this->earningCreationService->createEarning(
                booking: $booking,
                type: EarningType::CONCIERGE_REFERRAL_1->value,
                amount: $amount,
                percentage: BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE,
                percentageOf: 'platform'
            );
            $totalReferralEarnings += $amount;
        }

        if ($booking->concierge->referringConcierge?->referringConcierge) {
            $amount = $remainder * (BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE / 100);
            $this->earningCreationService->createEarning(
                booking: $booking,
                type: EarningType::CONCIERGE_REFERRAL_2->value,
                amount: $amount,
                percentage: BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE,
                percentageOf: 'platform'
            );
            $totalReferralEarnings += $amount;
        }

        return $totalReferralEarnings;
    }

    private function calculateAndCreatePartnerEarnings(Booking $booking, float $remainder): float
    {
        $maxPartnerEarnings = $remainder * (BookingPercentages::MAX_PARTNER_EARNINGS_PERCENTAGE / 100);

        $conciergePartnerEarnings = $this->calculatePartnerEarnings(
            booking: $booking,
            remainder: $remainder,
            type: EarningType::PARTNER_CONCIERGE
        );
        $venuePartnerEarnings = $this->calculatePartnerEarnings($booking, $remainder, EarningType::PARTNER_VENUE);

        $totalPartnerEarnings = $conciergePartnerEarnings + $venuePartnerEarnings;

        if ($this->isSamePartnerForConciergeAndVenue($booking) && $totalPartnerEarnings > $maxPartnerEarnings) {
            $this->adjustPartnerEarnings($booking, $totalPartnerEarnings, $maxPartnerEarnings);
            $totalPartnerEarnings = $maxPartnerEarnings;
        }

        return $totalPartnerEarnings;
    }

    private function calculatePartnerEarnings(Booking $booking, float $remainder, EarningType $type): float
    {
        $user = $type === EarningType::PARTNER_CONCIERGE ? $booking->concierge->user : $booking->venue->user;

        if (! $user->partner_referral_id) {
            return 0;
        }

        $partner = Partner::query()->find($user->partner_referral_id);

        // Skip creating earnings if partner percentage is 0%
        if ($partner->percentage <= 0) {
            return 0;
        }

        // Calculate the maximum allowed amount (20% of the remainder)
        $maxAllowedAmount = $remainder * (BookingPercentages::MAX_PARTNER_EARNINGS_PERCENTAGE / 100);

        // Calculate the amount based on the partner's percentage
        $calculatedAmount = $remainder * ($partner->percentage / 100);

        // Cap the amount at the maximum allowed
        $amount = min($calculatedAmount, $maxAllowedAmount);

        $this->earningCreationService->createEarning(
            $booking,
            $type->value,
            $amount,
            $partner->percentage,
            'remainder'
        );

        $booking->{$type->value.'_id'} = $partner->id;
        $booking->{$type->value.'_fee'} = floor($amount);

        return $amount;
    }

    private function isSamePartnerForConciergeAndVenue(Booking $booking): bool
    {
        return $booking->concierge->user->partner_referral_id
            && $booking->venue->user->partner_referral_id
            && $booking->concierge->user->partner_referral_id === $booking->venue->user->partner_referral_id;
    }

    private function adjustPartnerEarnings(
        Booking $booking,
        float $totalPartnerEarnings,
        float $maxPartnerEarnings
    ): void {
        $adjustmentFactor = $maxPartnerEarnings / $totalPartnerEarnings;
        $booking->partner_concierge_fee = (int) round($booking->partner_concierge_fee * $adjustmentFactor);
        $booking->partner_venue_fee = (int) round($booking->partner_venue_fee * $adjustmentFactor);
        Earning::query()->where('booking_id', $booking->id)
            ->where('type', EarningType::PARTNER_CONCIERGE)
            ->update(['amount' => (int) $booking->partner_concierge_fee]);
        Earning::query()->where('booking_id', $booking->id)
            ->where('type', EarningType::PARTNER_VENUE)
            ->update(['amount' => (int) $booking->partner_venue_fee]);
    }
}
