<?php

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\Partner;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;

beforeEach(function () {
    $this->service = app(BookingCalculationService::class);
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
    $this->concierge = Concierge::factory()->create();
});

test('partner earnings when partner refers both venue and concierge in a prime booking', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 6]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);
        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'partner_venue', 360);
        assertEarningExists($booking, 'partner_concierge', 360);

        $partnerEarningsByType = Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');

        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId);
    });
});

test('partner earnings when partner refers only venue', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 5]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);
        assertEarningExists($booking, 'partner_venue', 300);

        $partnerEarningsByType = Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_venue'])
            ->sum('amount');
        $partnerEarningsByUserId = Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_venue'])
            ->sum('amount');

        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId);
    });
});

test('partner earnings when partner refers only concierge', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 4]);

        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);
        assertEarningExists($booking, 'partner_concierge', 240);

        $partnerEarningsByType = Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_concierge'])
            ->sum('amount');

        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId);
    });
});

test('partner earnings with different percentages for venue and concierge', function () {
    Booking::withoutEvents(function () {
        $partnerVenue = Partner::factory()->create(['percentage' => 7]);
        $partnerConcierge = Partner::factory()->create(['percentage' => 3]);

        $this->venue->user->update(['partner_referral_id' => $partnerVenue->id]);
        $this->concierge->user->update(['partner_referral_id' => $partnerConcierge->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'partner_venue', 420);
        assertEarningExists($booking, 'partner_concierge', 180);

        $partnerEarningsByType = Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = Earning::where('user_id', $partnerConcierge->user_id)
            ->whereIn('type', ['partner_concierge'])
            ->sum('amount');

        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId);
    });
});

test('partner with not referred has not earning', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 0]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 2);

        $partnerEarningsByType = Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');

        expect($partnerEarningsByType)->toBe(0)
            ->and($partnerEarningsByUserId)->toBe(0)
            ->and($partnerEarningsByType)->toBe($partnerEarningsByUserId);
    });
});

test('partner earnings when partner refers both venue and concierge with 100% percentage, got cap to 20%', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 100]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);
        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge, $partner, $partner);

        assertEarningExists($booking, 'venue', $earningsAmount['venueEarning']);
        assertEarningExists($booking, 'concierge', $earningsAmount['conciergeEarning']);
        assertEarningExists($booking, 'partner_venue', $earningsAmount['partnerVenueEarning']);
        assertEarningExists($booking, 'partner_concierge', $earningsAmount['partnerConciergeEarning']);

        expect($booking->fresh()->platform_earnings)->toBe($earningsAmount['platFormEarnings']);
    });
});

test('partner earnings when partner refers concierge with 100% percentage, got cap to 20%', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 100]);

        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);
        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge, $partner);

        assertEarningExists($booking, 'venue', $earningsAmount['venueEarning']);
        assertEarningExists($booking, 'concierge', $earningsAmount['conciergeEarning']);
        assertEarningDoNotExists($booking, 'partner_venue', $earningsAmount['partnerVenueEarning']);
        assertEarningExists($booking, 'partner_concierge', $earningsAmount['partnerConciergeEarning']);

        expect($booking->fresh()->platform_earnings)->toBe($earningsAmount['platFormEarnings']);
    });
});

test('partner earnings when partner refers venue with 100% percentage, got cap to 20%', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 100]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);

        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge, null, $partner);

        assertEarningExists($booking, 'venue', $earningsAmount['venueEarning']);
        assertEarningExists($booking, 'concierge', $earningsAmount['conciergeEarning']);
        assertEarningExists($booking, 'partner_venue', $earningsAmount['partnerVenueEarning']);
        assertEarningDoNotExists($booking, 'partner_concierge', $earningsAmount['partnerConciergeEarning']);

        expect($booking->fresh()->platform_earnings)->toBe($earningsAmount['platFormEarnings']);
    });
});

test('partner earnings with different booking amount', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 5]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);
        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge, 50000);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'venue', 30000);
        assertEarningExists($booking, 'concierge', 5000);
        assertEarningExists($booking, 'partner_venue', (50000 - 30000 - 5000) * ($partner->percentage / 100));
        assertEarningExists($booking, 'partner_concierge', (50000 - 30000 - 5000) * ($partner->percentage / 100));

        expect($booking->fresh()->platform_earnings)->toBe(13500);
    });
});

test('partner earnings are capped at 20% when partner refers both venue and concierge in a prime booking', function () {
    Booking::withoutEvents(function () {
        // Create a partner with a high percentage to test the cap
        $partner = Partner::factory()->create(['percentage' => 30]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);
        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);

        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge, $partner, $partner);

        $partnerExpectedEarnings = (int) $earningsAmount['partnerConciergeEarning'] + $earningsAmount['partnerVenueEarning'];

        assertEarningExists($booking, 'partner_concierge', $earningsAmount['partnerConciergeEarning']);
        assertEarningExists($booking, 'partner_venue', $earningsAmount['partnerVenueEarning']);

        $partnerEarningsByType = (int) Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = (int) Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');

        // Assert that total partner earnings are equal to 20% of the booking fee
        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId)
            ->and($partnerEarningsByType)->toBe((int) $partnerExpectedEarnings)
            ->and($partnerEarningsByUserId)->toBe((int) $partnerExpectedEarnings)
            ->and($partnerExpectedEarnings)->toBeLessThanOrEqual($booking->total_fee * 0.20)
            ->and($partnerEarningsByType)->toBeLessThanOrEqual($booking->total_fee * 0.20)
            ->and($partnerEarningsByUserId)->toBeLessThanOrEqual($booking->total_fee * 0.20);
    });
});

test('partner earnings are capped at 20% when partner refers concierge in a prime booking', function () {
    Booking::withoutEvents(function () {
        // Create a partner with a high percentage to test the cap
        $partner = Partner::factory()->create(['percentage' => 30]);

        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);

        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge, $partner);

        $partnerExpectedEarnings = (int) $earningsAmount['partnerConciergeEarning'] + $earningsAmount['partnerVenueEarning'];

        assertEarningExists($booking, 'partner_concierge', $earningsAmount['partnerConciergeEarning']);
        assertEarningDoNotExists($booking, 'partner_venue', $earningsAmount['partnerVenueEarning']);

        $partnerEarningsByType = (int) Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = (int) Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');

        // Assert that total partner earnings are equal to 20% of the booking fee
        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId)
            ->and($partnerEarningsByType)->toBe((int) $partnerExpectedEarnings)
            ->and($partnerEarningsByUserId)->toBe((int) $partnerExpectedEarnings)
            ->and($partnerExpectedEarnings)->toBeLessThanOrEqual($booking->total_fee * 0.20)
            ->and($partnerEarningsByType)->toBeLessThanOrEqual($booking->total_fee * 0.20)
            ->and($partnerEarningsByUserId)->toBeLessThanOrEqual($booking->total_fee * 0.20);
    });
});

test('partner earnings are capped at 20% when partner refers venue in a prime booking', function () {
    Booking::withoutEvents(function () {
        // Create a partner with a high percentage to test the cap
        $partner = Partner::factory()->create(['percentage' => 30]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 3);

        $earningsAmount =
            getAllEarningsAmount($booking->total_fee, $this->venue, $this->concierge, null, $partner);

        $partnerExpectedEarnings = (int) $earningsAmount['partnerConciergeEarning'] + $earningsAmount['partnerVenueEarning'];

        assertEarningDoNotExists($booking, 'partner_concierge', $earningsAmount['partnerConciergeEarning']);
        assertEarningExists($booking, 'partner_venue', $earningsAmount['partnerVenueEarning']);

        $partnerEarningsByType = (int) Earning::where('booking_id', $booking->id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');
        $partnerEarningsByUserId = (int) Earning::where('user_id', $partner->user_id)
            ->whereIn('type', ['partner_venue', 'partner_concierge'])
            ->sum('amount');

        // Assert that total partner earnings are equal to 20% of the booking fee
        expect($partnerEarningsByType)->toBe($partnerEarningsByUserId)
            ->and($partnerEarningsByType)->toBe((int) $partnerExpectedEarnings)
            ->and($partnerEarningsByUserId)->toBe((int) $partnerExpectedEarnings)
            ->and($partnerExpectedEarnings)->toBeLessThanOrEqual($booking->total_fee * 0.20)
            ->and($partnerEarningsByType)->toBeLessThanOrEqual($booking->total_fee * 0.20)
            ->and($partnerEarningsByUserId)->toBeLessThanOrEqual($booking->total_fee * 0.20);
    });
});
