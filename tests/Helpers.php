<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\ScheduleTemplate;
use App\Models\Venue;

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

if (! function_exists('getAllEarnings')) {
    function getAllEarnings($bookingAmount, $venue, $concierge, $partnerVenue, $partnerConcierge): array
    {
        $venueEarning = ($venue->payout_venue / 100) * $bookingAmount;
        $conciergeEarning = ($concierge->payout_percentage / 100) * $bookingAmount;
        $remainderForPartner = $bookingAmount - $venueEarning - $conciergeEarning;
        $partnerVenueEarning = ($partnerVenue->percentage / 100) * $remainderForPartner;
        $partnerConciergeEarning = ($partnerConcierge->percentage / 100) * ($remainderForPartner);
        $platFormEarnings = (int) ($remainderForPartner - $partnerVenueEarning - $partnerConciergeEarning);

        return [$venueEarning, $conciergeEarning, $partnerVenueEarning, $partnerConciergeEarning, $platFormEarnings];
    }
}
