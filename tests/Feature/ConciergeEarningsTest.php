<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;

beforeEach(function () {
    $this->service = app(BookingCalculationService::class);
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
});

test('concierge earnings are calculated correctly for a single booking', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $booking = createBooking($this->venue, $concierge, 20000);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'concierge', 2000);
        expect($booking->fresh()->concierge_earnings)->toBe(2000);
    });
});

test('concierge earnings are summed correctly for multiple bookings', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $bookings = [
            createBooking($this->venue, $concierge),
            createBooking($this->venue, $concierge, 30000),
            createBooking($this->venue, $concierge, 15000),
        ];

        foreach ($bookings as $booking) {
            $this->service->calculateEarnings($booking);
        }

        $totalEarnings = (int) Earning::where('user_id', $concierge->user_id)
            ->where('type', 'concierge')
            ->sum('amount');

        expect($totalEarnings)->toBe(6500); // 2000 + 3000 + 1500
    });
});

test('concierge earnings with different payout percentages', function () {
    Booking::withoutEvents(function () {
        $testCases = [
            ['booking_amount' => 10000, 'expected_earning' => 10000 * (10 / 100)],
            ['booking_amount' => 20000, 'expected_earning' => 20000 * (10 / 100)],
            ['booking_amount' => 15000, 'expected_earning' => 15000 * (10 / 100)],
        ];

        foreach ($testCases as $case) {
            $concierge = Concierge::factory()->create();
            $booking = createBooking($this->venue, $concierge, $case['booking_amount']);

            $this->service->calculateEarnings($booking);

            assertEarningExists($booking, 'concierge', $case['expected_earning']);
        }
    });
});

test('concierge earnings for bookings with different venues', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $venue1 = Venue::factory()->create(['payout_venue' => 60]);
        $venue2 = Venue::factory()->create(['payout_venue' => 70]);

        $booking1 = createBooking($venue1, $concierge);
        $booking2 = createBooking($venue2, $concierge);

        $this->service->calculateEarnings($booking1);
        $this->service->calculateEarnings($booking2);

        $totalEarnings = Earning::where('user_id', $concierge->user_id)
            ->where('type', 'concierge')
            ->sum('amount');

        expect((int) $totalEarnings)->toBe(4000); // 2000 + 2000
    });
});

test('concierge earnings are rounded correctly', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $booking = createBooking($this->venue, $concierge, 10000);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'concierge', 1000);
    });
});

test('concierge earnings for a booking with zero amount', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $booking = createBooking($this->venue, $concierge, 0);

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'concierge', 0);
    });
});

test('concierge earnings are calculated correctly for large booking amounts', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $booking = createBooking($this->venue, $concierge, 1000000); // 1 million

        $this->service->calculateEarnings($booking);

        assertEarningExists($booking, 'concierge', 1000000 * (10 / 100));
    });
});

test('concierge earnings sum matches individual booking calculations', function () {
    Booking::withoutEvents(function () {
        $concierge = Concierge::factory()->create();
        $bookingAmounts = [15000, 25000, 30000, 10000, 50000];

        $expectedTotal = 0;
        foreach ($bookingAmounts as $amount) {
            $booking = createBooking($this->venue, $concierge, $amount);
            $this->service->calculateEarnings($booking);
            $expectedTotal += $amount * 0.10;
        }

        $actualTotal = (int) Earning::where('user_id', $concierge->user_id)
            ->where('type', 'concierge')
            ->sum('amount');

        expect($actualTotal)->toBe((int) $expectedTotal);
    });
});
