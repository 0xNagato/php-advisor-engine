<?php

/** @noinspection NullPointerExceptionInspection */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;
use App\Services\Booking\EarningCreationService;
use App\Services\Booking\NonPrimeEarningsCalculationService;
use App\Services\Booking\PrimeEarningsCalculationService;

beforeEach(function () {
    $earningCreationService = new EarningCreationService;
    $primeEarningsCalculationService = new PrimeEarningsCalculationService($earningCreationService);
    $nonPrimeEarningsCalculationService = new NonPrimeEarningsCalculationService($earningCreationService);

    $this->service = new BookingCalculationService(
        $primeEarningsCalculationService,
        $nonPrimeEarningsCalculationService
    );

    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

    $this->partialMock(Concierge::class, function ($mock) {
        $mock->shouldReceive('getAttribute')->with('payout_percentage')->andReturn(10);
    });
});

test('scenario 1: partner referred both concierge and venue', function () {
    Booking::withoutEvents(function () {
        $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);
        $this->venue->user->update(['partner_referral_id' => $this->partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'venue', 12000);
        assertEarningExists($booking, 'concierge', 2000);
        assertEarningExists($booking, 'partner_venue', 360);
        assertEarningExists($booking, 'partner_concierge', 360);
        expect($booking->fresh()->platform_earnings)->toBe(5280);
    });
});

test('scenario 2: different partners referred concierge and venue', function () {
    Booking::withoutEvents(function () {
        $partnerConcierge = Partner::factory()->create(['percentage' => 6]);
        $partnerVenue = Partner::factory()->create(['percentage' => 6]);

        $this->concierge->user->update(['partner_referral_id' => $partnerConcierge->id]);
        $this->venue->user->update(['partner_referral_id' => $partnerVenue->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'venue', 12000);
        assertEarningExists($booking, 'concierge', 2000);
        assertEarningExists($booking, 'partner_venue', 360);
        assertEarningExists($booking, 'partner_concierge', 360);
        expect($booking->fresh()->platform_earnings)->toBe(5280);
    });
});

test('scenario 3: concierge with level 1 referral', function () {
    Booking::withoutEvents(function () {
        $referringConcierge = Concierge::factory()->create();
        $this->concierge->user->update(['concierge_referral_id' => $referringConcierge->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);
        assertEarningExists($booking, 'venue', 12000);
        assertEarningExists($booking, 'concierge', 2000);
        assertEarningExists($booking, 'concierge_referral_1', 600);
        expect($booking->fresh()->platform_earnings)->toBe(5400);
    });
});

test('scenario 4: concierge with level 1 and level 2 referrals', function () {
    Booking::withoutEvents(function () {
        $referringConcierge1 = Concierge::factory()->create();
        $referringConcierge2 = Concierge::factory()->create();
        $this->concierge->user->update(['concierge_referral_id' => $referringConcierge1->id]);
        $referringConcierge1->user->update(['concierge_referral_id' => $referringConcierge2->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'venue', 12000);
        assertEarningExists($booking, 'concierge', 2000);
        assertEarningExists($booking, 'concierge_referral_1', 600);
        assertEarningExists($booking, 'concierge_referral_2', 300);
        expect($booking->fresh()->platform_earnings)->toBe(5100);
    });
});

test('non prime booking calculation', function () {
    Booking::withoutEvents(function () {
        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        $this->assertDatabaseCount('earnings', 2);
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1800);

        $freshBooking = $booking->fresh();
        expect($freshBooking->concierge_earnings)->toBe(1800)
            ->and($freshBooking->venue_earnings)->toBe(-2200)
            ->and($freshBooking->platform_earnings)->toBe(400);
    });
});

test('non prime booking with different guest count', function () {
    Booking::withoutEvents(function () {
        $booking = createNonPrimeBooking($this->venue, $this->concierge, 5);

        $this->service->calculateNonPrimeEarnings($booking);

        $this->assertDatabaseCount('earnings', 2);
        assertEarningExists($booking, 'venue_paid', -5500);
        assertEarningExists($booking, 'concierge_bounty', 4500);

        $freshBooking = $booking->fresh();
        expect($freshBooking->concierge_earnings)->toBe(4500)
            ->and($freshBooking->venue_earnings)->toBe(-5500)
            ->and($freshBooking->platform_earnings)->toBe(1000);
    });
});

test('non prime booking with custom fee', function () {
    Booking::withoutEvents(function () {
        $this->venue->update(['non_prime_fee_per_head' => 15]);
        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        $this->assertDatabaseCount('earnings', 2);
        assertEarningExists($booking, 'venue_paid', -3300);
        assertEarningExists($booking, 'concierge_bounty', 2700);

        $freshBooking = $booking->fresh();
        expect($freshBooking->concierge_earnings)->toBe(2700)
            ->and($freshBooking->venue_earnings)->toBe(-3300)
            ->and($freshBooking->platform_earnings)->toBe(600);
    });
});
