<?php

namespace App\Services\Booking;

use App\Constants\BookingPercentages;
use App\Enums\EarningType;
use App\Models\Booking;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\Concierge;
use Exception;
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
            $scheduleTemplate = ScheduleTemplate::query()->find($booking->schedule_template_id);
            if ($scheduleTemplate && $scheduleTemplate->price_per_head) {
                $pricePerHead = $scheduleTemplate->price_per_head;
            }
        }

        $fee = $pricePerHead * $booking->guest_count;
        $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
        $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
        $platform_venue = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $platform_earnings = $platform_concierge + $platform_venue;
        
        // Calculate partner earnings from the platform earnings
        $partnerEarnings = $this->calculateAndCreatePartnerEarnings($booking, $platform_earnings);
        
        // Calculate concierge referral earnings from the platform earnings
        $referralEarnings = $this->calculateAndCreateReferralEarnings($booking, $platform_earnings);
        
        // Adjust platform earnings after partner and referral payments
        $platform_earnings -= ($partnerEarnings + $referralEarnings);
        
        // Venue pays for everything (concierge earnings + platform earnings + partner earnings + referral earnings)
        $venue_earnings = ($concierge_earnings + $platform_earnings + $partnerEarnings + $referralEarnings) * -1;

        try {
            $this->createNonPrimeEarnings($booking, $venue_earnings, $concierge_earnings);

            $booking->update([
                'concierge_earnings' => $concierge_earnings * 100,
                'venue_earnings' => $venue_earnings * 100,
                'platform_earnings' => $platform_earnings * 100,
            ]);
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            Log::error('Failed to create earnings records', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    private function calculateAndCreateReferralEarnings(Booking $booking, float $remainder): float
    {
        $totalReferralEarnings = 0;

        // Get the referring concierge through the user's concierge_referral_id
        if ($booking->concierge->user->concierge_referral_id) {
            $amount = $remainder * (BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE / 100);
            $this->earningCreationService->createEarning(
                booking: $booking,
                type: EarningType::CONCIERGE_REFERRAL_1->value,
                amount: $amount * 100, // Convert to cents
                percentage: BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE,
                percentageOf: 'platform'
            );
            $totalReferralEarnings += $amount;
            
            // Check for second level referral
            $referringConcierge = Concierge::query()->find($booking->concierge->user->concierge_referral_id);
            if ($referringConcierge && $referringConcierge->user->concierge_referral_id) {
                $amount = $remainder * (BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE / 100);
                $this->earningCreationService->createEarning(
                    booking: $booking,
                    type: EarningType::CONCIERGE_REFERRAL_2->value,
                    amount: $amount * 100, // Convert to cents
                    percentage: BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE,
                    percentageOf: 'platform'
                );
                $totalReferralEarnings += $amount;
            }
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

        // Calculate the maximum allowed amount (20% of the remainder)
        $maxAllowedAmount = $remainder * (BookingPercentages::MAX_PARTNER_EARNINGS_PERCENTAGE / 100);

        // Calculate the amount based on the partner's percentage
        $calculatedAmount = $remainder * ($partner->percentage / 100);

        // Cap the amount at the maximum allowed
        $amount = min($calculatedAmount, $maxAllowedAmount);

        $this->earningCreationService->createEarning(
            $booking,
            $type->value,
            $amount * 100, // Convert to cents
            $partner->percentage,
            'remainder'
        );

        $booking->{$type->value.'_id'} = $partner->id;
        $booking->{$type->value.'_fee'} = $amount * 100; // Convert to cents
        $booking->save();

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
        $booking->partner_concierge_fee *= $adjustmentFactor;
        $booking->partner_venue_fee *= $adjustmentFactor;
        
        // Update the earnings records
        $booking->earnings()
            ->where('type', EarningType::PARTNER_CONCIERGE->value)
            ->update(['amount' => $booking->partner_concierge_fee]);
            
        $booking->earnings()
            ->where('type', EarningType::PARTNER_VENUE->value)
            ->update(['amount' => $booking->partner_venue_fee]);
            
        // Save the booking to persist the changes
        $booking->save();
    }
}
