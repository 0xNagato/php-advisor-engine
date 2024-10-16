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

test('partner earnings with 100% percentage (edge case)', function () {
    Booking::withoutEvents(function () {
        $partner = Partner::factory()->create(['percentage' => 100]);

        $this->venue->user->update(['partner_referral_id' => $partner->id]);
        $this->concierge->user->update(['partner_referral_id' => $partner->id]);

        $booking = createBooking($this->venue, $this->concierge);

        $this->service->calculateEarnings($booking);

        $this->assertDatabaseCount('earnings', 4);
        assertEarningExists($booking, 'venue', 12000);
        assertEarningExists($booking, 'concierge', 2000);
        assertEarningExists($booking, 'partner_venue', 6000);
        assertEarningExists($booking, 'partner_concierge', 6000);

        expect($booking->fresh()->platform_earnings)->toBe(-6000);
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
