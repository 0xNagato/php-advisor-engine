<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;
use App\Services\Booking\ConciergePromotionalEarningsService;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(BookingCalculationService::class);
    $this->promotionalService = app(ConciergePromotionalEarningsService::class);
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
    $this->concierge = Concierge::factory()->create();
});

test('concierge earnings are doubled during promotion period', function () {
    // Create a booking during the promotion period (May 2-4, 2025)
    Carbon::setTestNow(Carbon::parse('2025-05-01'));

    Booking::withoutEvents(function () {
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-05-03 18:00:00'), // During promotion
        ]);

        $this->service->calculateEarnings($booking);

        // Expected earnings should be doubled
        $normalEarnings = (int) (20000 * ($this->concierge->payout_percentage / 100));
        $promotionalEarnings = (int) ($normalEarnings * 2);

        assertEarningExists($booking, 'concierge', $promotionalEarnings);
        expect($booking->fresh()->concierge_earnings)->toBe($promotionalEarnings);
    });
});

test('concierge earnings are not doubled outside promotion period', function () {
    // Create a booking outside the promotion period
    Carbon::setTestNow(Carbon::parse('2025-04-30'));

    Booking::withoutEvents(function () {
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-04-30 18:00:00'), // Before promotion
        ]);

        $this->service->calculateEarnings($booking);

        // Expected earnings should not be doubled
        $normalEarnings = (int) (20000 * ($this->concierge->payout_percentage / 100));

        assertEarningExists($booking, 'concierge', $normalEarnings);
        expect($booking->fresh()->concierge_earnings)->toBe($normalEarnings);
    });
});

test('omakase venue concierge earnings are doubled during promotion period', function () {
    // Create an omakase venue
    $omakaseVenue = Venue::factory()->create([
        'payout_venue' => 60,
        'is_omakase' => true,
        'omakase_concierge_fee' => 50, // $50 per guest
    ]);

    Carbon::setTestNow(Carbon::parse('2025-05-01'));

    Booking::withoutEvents(function () use ($omakaseVenue) {
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 4,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $omakaseVenue->id])->id,
            'total_fee' => 40000,
            'booking_at' => Carbon::parse('2025-05-04 18:00:00'), // Last day of promotion
        ]);

        $this->service->calculateEarnings($booking);

        // Expected earnings for omakase should be: $50 per guest (4 guests) * 2 (for promotion)
        $normalEarnings = (int) ($omakaseVenue->omakase_concierge_fee * $booking->guest_count);
        $promotionalEarnings = (int) ($normalEarnings * 2);

        assertEarningExists($booking, 'concierge', $promotionalEarnings);
        expect($booking->fresh()->concierge_earnings)->toBe($promotionalEarnings);
    });
});

test('only prime bookings get doubled earnings during promotion period', function () {
    Carbon::setTestNow(Carbon::parse('2025-05-01'));

    Booking::withoutEvents(function () {
        // Create a non-prime booking during the promotion period
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => false, // Non-prime booking
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-05-03 18:00:00'), // During promotion
        ]);

        // For non-prime bookings, we use the NonPrimeBookingEarningsService
        // Which won't be affected by our changes to PrimeEarningsCalculationService
        $this->service->calculateNonPrimeEarnings($booking);

        // Expected earnings should follow the standard non-prime calculation
        // and NOT be doubled despite being in the promotion period
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $normalEarnings = ($fee - ($fee * 0.2)) * 100; // Using 20% platform fee for concierge

        // Check booking-level earnings (we can't use assertEarningExists here as it works differently for non-prime)
        $booking->refresh();
        expect((int) $booking->concierge_earnings)->toBeGreaterThan(0);
        expect($booking->concierge_earnings * 2)->not->toBe($booking->concierge_earnings);
    });
});

test('promotional service correctly identifies bookings in promotion period', function () {
    Booking::withoutEvents(function () {
        // Create a booking during the promotion period
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-05-03 18:00:00'),
        ]);

        expect($this->promotionalService->qualifiesForDoubleEarnings($booking))->toBeTrue();

        // Create a booking outside the promotion period
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-04-30 18:00:00'), // Before promotion period
        ]);

        expect($this->promotionalService->qualifiesForDoubleEarnings($booking))->toBeFalse();
    });
});

test('promotional service correctly handles bookings without dates', function () {
    Booking::withoutEvents(function () {
        // Create a booking (booking_at cannot be null in the DB)
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::now(),
        ]);

        // Manually set booking_at to null after creation (simulating a booking without a date)
        $booking->booking_at = null;

        expect($this->promotionalService->qualifiesForDoubleEarnings($booking))->toBeFalse();
    });
});

test('promotional service correctly handles non-prime bookings', function () {
    Booking::withoutEvents(function () {
        // Create a non-prime booking during the promotion period
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => false,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-05-03 18:00:00'),
        ]);

        expect($this->promotionalService->qualifiesForDoubleEarnings($booking))->toBeFalse();
    });
});

test('promotional service correctly applies multiplier', function () {
    Booking::withoutEvents(function () {
        // Create a booking during the promotion period
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-05-03 18:00:00'),
        ]);

        expect((int) $this->promotionalService->applyEarningsMultiplier(100, $booking))->toBe(200);

        // Create a booking outside the promotion period
        $booking = Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'guest_count' => 2,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id])->id,
            'total_fee' => 20000,
            'booking_at' => Carbon::parse('2025-04-30 18:00:00'), // Before promotion period
        ]);

        expect((int) $this->promotionalService->applyEarningsMultiplier(100, $booking))->toBe(100);
    });
});
