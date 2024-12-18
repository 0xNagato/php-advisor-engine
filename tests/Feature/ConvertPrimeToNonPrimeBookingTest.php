<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;
use App\Services\BookingService;

beforeEach(function () {
    $this->service = app(BookingCalculationService::class);
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
    $this->concierge = Concierge::factory()->create();
});

test('concierge earnings are calculated correctly for a single booking', function () {
    Booking::withoutEvents(function () {

        $booking = createBooking($this->venue, $this->concierge, 20000);
        $this->service->calculateEarnings($booking);

        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge);

        $this->assertDatabaseCount('earnings', 2);
        expect($booking->is_prime)->toBe(true);
        assertEarningExists($booking, 'venue', $earningsAmount['venueEarning']);
        assertEarningExists($booking, 'concierge', $earningsAmount['conciergeEarning']);

        app(BookingService::class)->convertToNonPrime($booking);

        $booking->fresh();

        $this->service->calculateEarnings($booking);

        $earningsNonPrimeAmounts =
            getNonPrimeEarningsAmounts($booking);

        expect($booking->is_prime)->toBe(0)
            ->and($booking->concierge_earnings)->toBe($earningsNonPrimeAmounts['concierge_earnings'])
            ->and($booking->venue_earnings)->toBe($earningsNonPrimeAmounts['venue_earnings'])
            ->and($booking->platform_earnings)->toBe($earningsNonPrimeAmounts['platform_earnings'])
            ->and($booking->total_fee)->toBe(0);

        $this->assertDatabaseCount('earnings', 2);
        assertEarningDoNotExists($booking, 'venue', $earningsAmount['venueEarning']);
        assertEarningDoNotExists($booking, 'concierge', $earningsAmount['conciergeEarning']);

        assertEarningExists($booking, 'venue_paid', $earningsNonPrimeAmounts['venue_earnings']);
        assertEarningExists($booking, 'concierge_bounty', $earningsNonPrimeAmounts['concierge_earnings']);
    });
});
