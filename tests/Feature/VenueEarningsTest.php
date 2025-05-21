<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;

beforeEach(function () {
    $this->service = app(BookingCalculationService::class);
});

test('venue earnings are calculated correctly for a single booking', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge = Concierge::factory()->create();

        $booking = createBooking($venue, $concierge);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'venue', 12000);
        expect($booking->fresh()->venue_earnings)->toBe(12000);
    });
});

test('venue earnings are summed correctly for multiple bookings', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge = Concierge::factory()->create();
        $bookings = [
            createBooking($venue, $concierge),
            createBooking($venue, $concierge, 30000),
            createBooking($venue, $concierge, 15000),
        ];

        foreach ($bookings as $booking) {
            $this->service->calculateEarnings($booking);
        }

        $totalEarnings = Earning::where('user_id', $venue->user_id)
            ->where('type', 'venue')
            ->sum('amount');

        expect((int) $totalEarnings)->toBe(39000); // (20000 + 30000 + 15000) * 0.60
    });
});

test('venue earnings with different payout percentages', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $testCases = [
            ['payout_venue' => 50, 'booking_amount' => 10000, 'expected_earning' => 5000],
            ['payout_venue' => 70, 'booking_amount' => 20000, 'expected_earning' => 14000],
            ['payout_venue' => 65, 'booking_amount' => 15000, 'expected_earning' => 9750],
        ];

        foreach ($testCases as $case) {
            $venue = Venue::factory()->create(['payout_venue' => $case['payout_venue']]);
            $booking = createBooking($venue, $concierge, $case['booking_amount']);

            $this->service->calculateEarnings($booking);

            assertEarningExists($booking, 'venue', $case['expected_earning']);
        }
    });
});

test('venue earnings for bookings with different concierges', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge1 = Concierge::factory()->create();
        $concierge2 = Concierge::factory()->create();

        $booking1 = createBooking($venue, $concierge1);
        $booking2 = createBooking($venue, $concierge2);

        $this->service->calculateEarnings($booking1);
        $this->service->calculateEarnings($booking2);

        $totalEarnings = Earning::where('user_id', $venue->user_id)
            ->where('type', 'venue')
            ->sum('amount');

        expect((int) $totalEarnings)->toBe(24000); // (20000 * 0.60) * 2
    });
});

test('venue earnings are rounded correctly', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 62]);
        $concierge = Concierge::factory()->create();
        $booking = createBooking($venue, $concierge, 10000);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'venue', 6200);
    });
});

test('venue earnings for a booking with zero amount', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge = Concierge::factory()->create();
        $booking = createBooking($venue, $concierge, 0);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'venue', 0);
    });
});

test('venue earnings are calculated correctly for large booking amounts', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge = Concierge::factory()->create();
        $booking = createBooking($venue, $concierge, 1000000); // 1 million

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'venue', 600000);
    });
});

test('venue earnings sum matches individual booking calculations', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge = Concierge::factory()->create();
        $bookingAmounts = [15000, 25000, 30000, 10000, 50000];

        $expectedTotal = 0;
        foreach ($bookingAmounts as $amount) {
            $booking = createBooking($venue, $concierge, $amount);
            $this->service->calculateEarnings($booking);
            $expectedTotal += $amount * 0.60;
        }

        $actualTotal = (int) Earning::where('user_id', $venue->user_id)
            ->where('type', 'venue')
            ->sum('amount');

        expect($actualTotal)->toBe((int) $expectedTotal);
    });
});

test('venue earnings are not affected by concierge payout', function () {
    Booking::withoutEvents(function () {
        $venue = Venue::factory()->create(['payout_venue' => 60]);
        $concierge = Concierge::factory()->create();
        $booking = createBooking($venue, $concierge, 20000);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'venue', 12000); // Should still be 60% of 20000
    });
});
