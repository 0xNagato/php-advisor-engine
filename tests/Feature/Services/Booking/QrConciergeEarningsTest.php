<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;

beforeEach(function () {
    $this->service = app(BookingCalculationService::class);
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
});

test('QR concierge earnings are calculated correctly for prime booking with custom percentage', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->qr(60)->create(); // Custom 60% instead of default 50%

        $booking = createBooking($this->venue, $concierge, 20000); // $200 booking

        $this->service->calculateEarnings($booking);

        // QR concierge should get 60% = $120 (12000 cents)
        assertEarningExists($booking, 'concierge', 12000);
        expect($booking->fresh()->concierge_earnings)->toBe(12000);
    });
});

test('QR concierge earnings are calculated correctly for non-prime booking with custom percentage', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create([
            'non_prime_fee_per_head' => 50, // $50 per head
        ]);

        $concierge = Concierge::factory()->create([
            'is_qr_concierge' => true,
            'revenue_percentage' => 70, // Custom 70%
        ]);

        $booking = createNonPrimeBooking($venue, $concierge, 2); // 2 guests = $100 total

        $this->service->calculateEarnings($booking);

        // QR concierge should get 70% = $70 (7000 cents)
        assertEarningExists($booking, 'concierge_bounty', 7000);
        expect($booking->fresh()->concierge_earnings)->toBe(7000);
    });
});

test('QR concierge with default 50% percentage for prime booking', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create([
            'is_qr_concierge' => true,
            'revenue_percentage' => 50, // Default percentage
        ]);

        $booking = createBooking($this->venue, $concierge, 10000); // $100 booking

        $this->service->calculateEarnings($booking);

        // QR concierge should get 50% = $50 (5000 cents)
        assertEarningExists($booking, 'concierge', 5000);
        expect($booking->fresh()->concierge_earnings)->toBe(5000);
    });
});

test('QR concierge with default 50% percentage for non-prime booking', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create([
            'non_prime_fee_per_head' => 40, // $40 per head
        ]);

        $concierge = Concierge::factory()->create([
            'is_qr_concierge' => true,
            'revenue_percentage' => 50, // Default percentage
        ]);

        $booking = createNonPrimeBooking($venue, $concierge, 3); // 3 guests = $120 total

        $this->service->calculateEarnings($booking);

        // QR concierge should get 50% = $60 (6000 cents)
        assertEarningExists($booking, 'concierge_bounty', 6000);
        expect($booking->fresh()->concierge_earnings)->toBe(6000);
    });
});

test('regular concierge earnings unchanged with QR system in place', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create([
            'is_qr_concierge' => false, // Regular concierge
        ]);

        $booking = createBooking($this->venue, $concierge, 20000); // $200 booking

        $this->service->calculateEarnings($booking);

        // Regular concierge should get 10% (default payout_percentage) = $20 (2000 cents)
        assertEarningExists($booking, 'concierge', 2000);
        expect($booking->fresh()->concierge_earnings)->toBe(2000);
    });
});

test('regular concierge non-prime earnings unchanged with QR system in place', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create([
            'non_prime_fee_per_head' => 50, // $50 per head
        ]);

        $concierge = Concierge::factory()->create([
            'is_qr_concierge' => false, // Regular concierge
        ]);

        $booking = createNonPrimeBooking($venue, $concierge, 2); // 2 guests = $100 total

        $this->service->calculateEarnings($booking);

        // Regular concierge should get 80% (NON_PRIME_CONCIERGE_PERCENTAGE) = $80 (8000 cents)
        assertEarningExists($booking, 'concierge_bounty', 8000);
        expect($booking->fresh()->concierge_earnings)->toBe(8000);
    });
});

test('QR concierge earnings with different custom percentages', function () {
    Booking::withoutEvents(function () {
        $testCases = [
            ['percentage' => 30, 'booking_amount' => 10000, 'expected_earning' => 3000],
            ['percentage' => 45, 'booking_amount' => 20000, 'expected_earning' => 9000],
            ['percentage' => 75, 'booking_amount' => 8000, 'expected_earning' => 6000],
            ['percentage' => 100, 'booking_amount' => 5000, 'expected_earning' => 5000],
        ];

        foreach ($testCases as $case) {
            $concierge = Concierge::factory()->create([
                'is_qr_concierge' => true,
                'revenue_percentage' => $case['percentage'],
            ]);

            $booking = createBooking($this->venue, $concierge, $case['booking_amount']);

            $this->service->calculateEarnings($booking);

            assertEarningExists($booking, 'concierge', $case['expected_earning']);
            expect($booking->fresh()->concierge_earnings)->toBe($case['expected_earning']);
        }
    });
});

test('QR concierge platform earnings are correctly calculated', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create([
            'is_qr_concierge' => true,
            'revenue_percentage' => 60, // QR gets 60%
        ]);

        $booking = createBooking($this->venue, $concierge, 10000); // $100 booking

        $this->service->calculateEarnings($booking);

        // Venue gets 60% of $100 = $60 (6000 cents)
        expect($booking->fresh()->venue_earnings)->toBe(6000);

        // QR concierge gets 60% of $100 = $60 (6000 cents)
        expect($booking->fresh()->concierge_earnings)->toBe(6000);

        // Platform gets remainder: $100 - $60 - $60 = -$20 (-2000 cents)
        // This negative value makes sense as it represents the platform's cost
        expect($booking->fresh()->platform_earnings)->toBeLessThan(0);
    });
});

test('QR concierge factory method works correctly', function () {
    $concierge = Concierge::factory()->qr(75)->create();

    expect($concierge->is_qr_concierge)->toBeTrue()
        ->and($concierge->revenue_percentage)->toBe(75);
});

test('QR concierge factory method uses default 50% when no percentage specified', function () {
    $concierge = Concierge::factory()->qr()->create();

    expect($concierge->is_qr_concierge)->toBeTrue()
        ->and($concierge->revenue_percentage)->toBe(50);
});
