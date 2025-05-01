<?php

use App\Constants\BookingPercentages;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;
use App\Services\Booking\ConciergePromotionalEarningsService;
use App\Services\Booking\EarningCreationService;
use App\Services\Booking\NonPrimeEarningsCalculationService;

beforeEach(function () {
    $earningCreationService = new EarningCreationService;
    $nonPrimeEarningsCalculationService = new NonPrimeEarningsCalculationService($earningCreationService);

    $promotionalService = new ConciergePromotionalEarningsService;
    $this->service = new BookingCalculationService(
        new \App\Services\Booking\PrimeEarningsCalculationService($earningCreationService, $promotionalService),
        $nonPrimeEarningsCalculationService
    );

    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 10]);
});

test('non prime booking with partner referral for concierge', function () {
    Booking::withoutEvents(function () {
        // Set up partner referral for concierge
        $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $partnerEarnings = $platformEarnings * ($this->partner->percentage / 100);

        // Check that partner earnings were created
        $this->assertDatabaseCount('earnings', 3); // venue_paid, concierge_bounty, partner_concierge
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'partner_concierge', (int) ($partnerEarnings * 100));

        $freshBooking = $booking->fresh();
        expect($freshBooking->partner_concierge_id)->toBe($this->partner->id)
            ->and($freshBooking->partner_concierge_fee)->toBe((int) ($partnerEarnings * 100));
    });
});

test('non prime booking with partner referral for venue', function () {
    Booking::withoutEvents(function () {
        // Set up partner referral for venue
        $this->venue->user->update(['partner_referral_id' => $this->partner->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $partnerEarnings = $platformEarnings * ($this->partner->percentage / 100);

        // Check that partner earnings were created
        $this->assertDatabaseCount('earnings', 3); // venue_paid, concierge_bounty, partner_venue
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'partner_venue', (int) ($partnerEarnings * 100));

        $freshBooking = $booking->fresh();
        expect($freshBooking->partner_venue_id)->toBe($this->partner->id)
            ->and($freshBooking->partner_venue_fee)->toBe((int) ($partnerEarnings * 100));
    });
});

test('non prime booking with partner referral for both concierge and venue', function () {
    Booking::withoutEvents(function () {
        $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);
        $this->venue->user->update(['partner_referral_id' => $this->partner->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);

        // Each partner earning is calculated independently
        $partnerEarningPerType = $platformEarnings * ($this->partner->percentage / 100);

        // Check that partner earnings were created
        $this->assertDatabaseCount('earnings', 4); // venue_paid, concierge_bounty, partner_concierge, partner_venue
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'partner_concierge', (int) ($partnerEarningPerType * 100)); // Convert to cents
        assertEarningExists($booking, 'partner_venue', (int) ($partnerEarningPerType * 100)); // Convert to cents

        $freshBooking = $booking->fresh();
        expect($freshBooking->partner_concierge_id)->toBe($this->partner->id)
            ->and($freshBooking->partner_concierge_fee)->toBe((int) ($partnerEarningPerType * 100))
            ->and($freshBooking->partner_venue_id)->toBe($this->partner->id)
            ->and($freshBooking->partner_venue_fee)->toBe((int) ($partnerEarningPerType * 100));
    });
});

test('non prime booking with partner referral exceeding max percentage', function () {
    Booking::withoutEvents(function () {
        // Set up partner with high percentage
        $this->partner->update(['percentage' => 30]);
        $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $maxPartnerEarnings = $platformEarnings * (BookingPercentages::MAX_PARTNER_EARNINGS_PERCENTAGE / 100);

        // Check that partner earnings were capped
        $this->assertDatabaseCount('earnings', 3); // venue_paid, concierge_bounty, partner_concierge
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'partner_concierge', (int) ($maxPartnerEarnings * 100));

        $freshBooking = $booking->fresh();
        expect($freshBooking->partner_concierge_id)->toBe($this->partner->id)
            ->and($freshBooking->partner_concierge_fee)->toBe((int) ($maxPartnerEarnings * 100));
    });
});

test('non prime booking with concierge referral', function () {
    Booking::withoutEvents(function () {
        // Create a referring concierge
        $referringConcierge = Concierge::factory()->create();
        // Set the concierge_referral_id on the user to the concierge's id
        $this->concierge->user->update(['concierge_referral_id' => $referringConcierge->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $referralEarnings = $platformEarnings * (BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE / 100);

        // Check that referral earnings were created
        $this->assertDatabaseCount('earnings', 3); // venue_paid, concierge_bounty, concierge_referral_1
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'concierge_referral_1', (int) ($referralEarnings * 100));
    });
});

test('non prime booking with two levels of concierge referral', function () {
    Booking::withoutEvents(function () {
        // Create two levels of referring concierges
        $referringConcierge2 = Concierge::factory()->create();
        $referringConcierge1 = Concierge::factory()->create();
        // Set up the referral chain using concierge_referral_id on the users
        $referringConcierge1->user->update(['concierge_referral_id' => $referringConcierge2->id]);
        $this->concierge->user->update(['concierge_referral_id' => $referringConcierge1->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $referralEarnings1 = $platformEarnings * (BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE / 100);
        $referralEarnings2 = $platformEarnings * (BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE / 100);

        // Check that both levels of referral earnings were created
        $this->assertDatabaseCount('earnings', 4); // venue_paid, concierge_bounty, concierge_referral_1, concierge_referral_2
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'concierge_referral_1', (int) ($referralEarnings1 * 100));
        assertEarningExists($booking, 'concierge_referral_2', (int) ($referralEarnings2 * 100));
    });
});

test('non prime booking with partner and concierge referrals', function () {
    Booking::withoutEvents(function () {
        // Set up partner referral for concierge
        $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);

        // Create a referring concierge
        $referringConcierge = Concierge::factory()->create();
        // Set the concierge_referral_id on the user to the concierge's id
        $this->concierge->user->update(['concierge_referral_id' => $referringConcierge->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);

        $this->service->calculateNonPrimeEarnings($booking);

        // Calculate expected earnings
        $fee = $this->venue->non_prime_fee_per_head * $booking->guest_count;
        $platformEarnings = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100) +
                           $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
        $partnerEarnings = $platformEarnings * ($this->partner->percentage / 100);
        $referralEarnings = $platformEarnings * (BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE / 100);

        // Check that both partner and referral earnings were created
        $this->assertDatabaseCount('earnings', 4); // venue_paid, concierge_bounty, partner_concierge, concierge_referral_1
        assertEarningExists($booking, 'venue_paid', -2200);
        assertEarningExists($booking, 'concierge_bounty', 1600);
        assertEarningExists($booking, 'partner_concierge', (int) ($partnerEarnings * 100));
        assertEarningExists($booking, 'concierge_referral_1', (int) ($referralEarnings * 100));

        $freshBooking = $booking->fresh();
        expect($freshBooking->partner_concierge_id)->toBe($this->partner->id)
            ->and($freshBooking->partner_concierge_fee)->toBe((int) ($partnerEarnings * 100));
    });
});
