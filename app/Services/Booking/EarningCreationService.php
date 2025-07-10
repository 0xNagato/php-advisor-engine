<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\Earning;
use App\Models\Partner;

class EarningCreationService
{
    public function createEarning(
        Booking $booking,
        string $type,
        float $amount,
        float $percentage,
        string $percentageOf
    ): void {
        Earning::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $this->getUserIdForEarningType($booking, $type),
            'type' => $type,
            'amount' => floor($amount),
            'currency' => $booking->currency,
            'percentage' => $percentage,
            'percentage_of' => $percentageOf,
        ]);
    }

    private function getUserIdForEarningType(Booking $booking, string $type): ?int
    {
        return match ($type) {
            'venue', 'venue_paid' => $booking->venue->user_id,
            'concierge', 'concierge_bounty' => $booking->concierge->user_id,
            'concierge_referral_1' => $booking->concierge->referringConcierge->user_id,
            'concierge_referral_2' => $booking->concierge->referringConcierge->referringConcierge->user_id,
            'partner_concierge' => Partner::query()->find($booking->concierge->user->partner_referral_id)->user_id,
            'partner_venue' => Partner::query()->find($booking->venue->user->partner_referral_id)->user_id,
            default => null,
        };
    }
}
