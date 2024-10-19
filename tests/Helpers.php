<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\ScheduleTemplate;
use App\Models\Venue;

const MAX_PARTNER_PERCENTAGE = 0.20;

if (! function_exists('createBooking')) {
    function createBooking($venue, $concierge, $amount = 20000)
    {
        return Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'total_fee' => $amount,
            'concierge_id' => $concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $venue->id])->id,
        ]);
    }
}

if (! function_exists('createNonPrimeBooking')) {
    function createNonPrimeBooking(Venue $venue, Concierge $concierge, int $guestCount = 2): Booking
    {
        return Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => false,
            'guest_count' => $guestCount,
            'concierge_id' => $concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $venue->id])->id,
            'total_fee' => $venue->non_prime_fee_per_head * $guestCount * 100,
        ]);
    }
}

if (! function_exists('assertEarningExists')) {
    function assertEarningExists($booking, $type, $amount): void
    {
        expect(Earning::where('booking_id', $booking->id)
            ->where('type', $type)
            ->where('amount', $amount)
            ->exists())->toBeTrue();
    }
}

if (! function_exists('assertEarningDoNotExists')) {
    function assertEarningDoNotExists($booking, $type, $amount): void
    {
        expect(! Earning::where('booking_id', $booking->id)
            ->where('type', $type)
            ->where('amount', $amount)
            ->exists())->toBeTrue();
    }
}

if (! function_exists('getAllEarningsAmount')) {
    function getAllEarningsAmount(
        float $bookingAmount,
        object $venue,
        object $concierge,
        ?object $partnerConcierge = null,
        ?object $partnerVenue = null
    ): array {

        $venueEarning = ($venue->payout_venue / 100) * $bookingAmount;
        $conciergeEarning = ($concierge->payout_percentage / 100) * $bookingAmount;
        $remainderForPartner = $bookingAmount - $venueEarning - $conciergeEarning;

        // Calculate the maximum allowed partner earnings (20% of booking amount)
        $maxPartnerEarnings = round(MAX_PARTNER_PERCENTAGE * $remainderForPartner, 2);

        // Calculate partner earnings, capping each at the maximum allowed
        $partnerConciergeEarning = $partnerVenueEarning = 0;

        if ($partnerConcierge) {
            $partnerConciergeEarning = min(
                ($partnerConcierge->percentage / 100) * $remainderForPartner,
                $maxPartnerEarnings
            );
        }

        if ($partnerVenue) {
            $partnerVenueEarning = min(
                ($partnerVenue->percentage / 100) * $remainderForPartner,
                $maxPartnerEarnings
            );
        }

        // Check if partners are the same and adjust if necessary
        if ($partnerConcierge && $partnerVenue && $partnerConcierge === $partnerVenue) {
            $totalPartnerEarning = $partnerConciergeEarning + $partnerVenueEarning;
            if ($totalPartnerEarning > $maxPartnerEarnings) {
                // Adjust partner earnings proportionally
                $adjustmentFactor = $maxPartnerEarnings / $totalPartnerEarning;
                $partnerConciergeEarning *= $adjustmentFactor;
                $partnerVenueEarning *= $adjustmentFactor;
            }
        }

        // Recalculate platform earnings
        $platFormEarnings = (int) ($remainderForPartner - $partnerVenueEarning - $partnerConciergeEarning);

        return [
            'venueEarning' => $venueEarning,
            'conciergeEarning' => $conciergeEarning,
            'partnerConciergeEarning' => $partnerConciergeEarning,
            'partnerVenueEarning' => $partnerVenueEarning,
            'platFormEarnings' => $platFormEarnings,
            'remainderForPartner' => $remainderForPartner,
        ];
    }
}
