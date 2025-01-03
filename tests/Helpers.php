<?php

use App\Constants\BookingPercentages;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Services\SalesTaxService;

const MAX_PARTNER_PERCENTAGE = 0.20;

if (! function_exists('createBooking')) {
    function createBooking($venue, $concierge, $amount = 20000)
    {
        $booking = Booking::factory()->create([
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $venue->id])->id,
            'concierge_id' => $concierge->id,
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'total_fee' => $amount,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax(
            $booking->venue->region,
            $booking->total_fee,
            noTax: config('app.no_tax')
        );

        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->region,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
        ]);

        return $booking;
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

if (! function_exists('getNonPrimeBookingEarnings')) {
    function getNonPrimeBookingEarnings(int $guestCount, Venue $venue): array
    {
        $fee = $guestCount * $venue->non_prime_fee_per_head;
        $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
        $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
        $platform_venue = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $platform_earnings = $platform_concierge + $platform_venue;
        $venue_earnings = ($concierge_earnings + $platform_earnings) * -1;

        return [
            'venue_earnings' => (int) $venue_earnings * 100,
            'concierge_earnings' => (int) $concierge_earnings * 100,
            'platform_earnings' => (int) $platform_earnings * 100,
        ];
    }
}

if (! function_exists('getNonPrimeEarningsAmounts')) {
    function getNonPrimeEarningsAmounts(Booking $booking): array
    {
        $fee = $booking->venue->non_prime_fee_per_head * $booking->guest_count;
        $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
        $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
        $platform_venue = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $platform_earnings = $platform_concierge + $platform_venue;
        $venue_earnings = ($concierge_earnings + $platform_earnings) * -1;

        return [
            'concierge_earnings' => $concierge_earnings * 100,
            'venue_earnings' => $venue_earnings * 100,
            'platform_earnings' => $platform_earnings * 100,
        ];
    }
}
