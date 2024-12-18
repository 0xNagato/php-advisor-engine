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

test('non-prime booking is converted to a prime booking correctly', function () {
    Booking::withoutEvents(function () {
        // Step 1: Create a non-prime booking
        $booking = createNonPrimeBooking($this->venue, $this->concierge, 10000);
        $this->service->calculateEarnings($booking);

        $nonPrimeEarningsAmounts =
            getNonPrimeEarningsAmounts($booking);

        // Assert the booking is non-prime initially
        expect($booking->is_prime)->toBe(false);
        assertEarningExists($booking, 'venue_paid', $nonPrimeEarningsAmounts['venue_earnings']);
        assertEarningExists($booking, 'concierge_bounty', $nonPrimeEarningsAmounts['concierge_earnings']);

        // Step 2: Convert it to a prime booking
        app(BookingService::class)->convertToPrime($booking);

        // Refresh the booking instance
        $booking->fresh();

        // Step 3: Recalculate earnings for the prime booking
        $this->service->calculateEarnings($booking);

        $primeEarningsAmounts =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge);

        // Assertions
        expect($booking->is_prime)->toBe(1)
            ->and($booking->concierge_earnings)->toBe($primeEarningsAmounts['conciergeEarning'])
            ->and($booking->venue_earnings)->toBe($primeEarningsAmounts['venueEarning']);

        // Ensure prior non-prime earnings records are removed
        assertEarningDoNotExists($booking, 'venue_paid', $nonPrimeEarningsAmounts['venue_earnings']);
        assertEarningDoNotExists($booking, 'concierge_bounty', $nonPrimeEarningsAmounts['concierge_earnings']);

        // Ensure new prime earnings are correctly stored
        assertEarningExists($booking, 'venue', $primeEarningsAmounts['venueEarning']);
        assertEarningExists($booking, 'concierge', $primeEarningsAmounts['conciergeEarning']);
    });
});
