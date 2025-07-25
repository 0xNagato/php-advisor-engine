<?php

use App\Actions\Partner\SetPartnerRevenueToZeroAndRecalculate;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\Partner;
use App\Models\Venue;
use App\Services\Booking\BookingCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculationService = app(BookingCalculationService::class);

    // Create test data
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
    $this->concierge = Concierge::factory()->create();

    // Create partners with different percentages
    $this->partnerWithEarnings = Partner::factory()->create(['percentage' => 15]);
    $this->partnerWithZeroPercent = Partner::factory()->create(['percentage' => 0]);
    $this->partnerWithoutBookings = Partner::factory()->create(['percentage' => 8]);

    // Associate venue and concierge with partners
    $this->venue->user->update(['partner_referral_id' => $this->partnerWithEarnings->id]);
    $this->concierge->user->update(['partner_referral_id' => $this->partnerWithEarnings->id]);
});

test('dry run mode shows correct statistics without making changes', function () {
    // Create a booking with partner earnings
    $booking = createBooking($this->venue, $this->concierge);

    // Set booking to confirmed status so it will be found by the query
    $booking->update(['status' => BookingStatus::CONFIRMED]);

    // Calculate earnings so the booking has partner earnings to be found
    $this->calculationService->calculateEarnings($booking);

    // Store original data
    $originalPartnerPercentage = $this->partnerWithEarnings->percentage;
    $originalEarningsCount = Earning::count();

    // Run in dry-run mode
    $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: true);

    // Verify statistics
    expect($result['dry_run'])->toBeTrue();
    expect($result['partners_found'])->toBe(13); // 10 from seeder + 3 from test setup
    expect($result['partners_with_non_zero_percentage'])->toBe(12); // 10 seeded (20%) + partnerWithEarnings (15%) + partnerWithoutBookings (8%)
    expect($result['partners_updated'])->toBe(12);
    expect($result['bookings_found'])->toBe(1);
    expect($result['bookings_recalculated'])->toBe(1);
    expect($result['inactive_bookings_found'])->toBe(0); // No inactive bookings in this test
    expect($result['errors'])->toBeEmpty();

    // Verify no actual changes were made
    expect($this->partnerWithEarnings->fresh()->percentage)->toBe($originalPartnerPercentage);
    expect(Earning::count())->toBe($originalEarningsCount);
});

test('live mode updates partner percentages to zero', function () {
    // Run the action in live mode
    $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

    // Verify partner percentages were updated
    expect($this->partnerWithEarnings->fresh()->percentage)->toBe(0);
    expect($this->partnerWithZeroPercent->fresh()->percentage)->toBe(0); // Should remain 0
    expect($this->partnerWithoutBookings->fresh()->percentage)->toBe(0);

    // Verify statistics
    expect($result['partners_updated'])->toBe(12); // 10 seeded + 2 from test setup (excluding the one with 0%)
});

test('recalculates bookings with partner earnings correctly', function () {
    Booking::withoutEvents(function () {
        // Create booking with partner earnings
        $booking = createBooking($this->venue, $this->concierge);
        $booking->update(['status' => BookingStatus::CONFIRMED]);
        $this->calculationService->calculateEarnings($booking);

        // Verify initial partner earnings exist
        $initialPartnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($initialPartnerEarnings)->toBeGreaterThan(0);

        $initialPlatformEarnings = $booking->platform_earnings;

        // Run the action
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        // Verify booking was recalculated
        $booking->refresh();

        // Partner earnings should now be 0
        $newPartnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($newPartnerEarnings)->toBe(0);

        // Platform earnings should increase (since partner earnings are now 0)
        expect($booking->platform_earnings)->toBeGreaterThan($initialPlatformEarnings);

        // Verify statistics
        expect($result['bookings_recalculated'])->toBe(1);
        expect($result['errors'])->toBeEmpty();
    });
});

test('handles bookings with different partner associations correctly', function () {
    Booking::withoutEvents(function () {
        // Create partner for concierge only
        $conciergePartner = Partner::factory()->create(['percentage' => 12]);
        $this->concierge->user->update(['partner_referral_id' => $conciergePartner->id]);

        $booking = createBooking($this->venue, $this->concierge);
        $booking->update(['status' => BookingStatus::CONFIRMED]);
        $this->calculationService->calculateEarnings($booking);

        // Verify concierge partner earnings exist
        expect($booking->partner_concierge_id)->toBe($conciergePartner->id);

        // Verify initial partner earnings exist
        $initialPartnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($initialPartnerEarnings)->toBeGreaterThan(0);

        // Run the action
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        $booking->refresh();

        // Verify partner earnings are zeroed after the action
        $newPartnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($newPartnerEarnings)->toBe(0);

        expect($result['bookings_recalculated'])->toBe(1);
    });
});

test('handles non-prime bookings with partner earnings', function () {
    Booking::withoutEvents(function () {
        // Ensure venue and concierge have partner associations
        $this->venue->user->update(['partner_referral_id' => $this->partnerWithEarnings->id]);
        $this->concierge->user->update(['partner_referral_id' => $this->partnerWithEarnings->id]);

        $booking = createNonPrimeBooking($this->venue, $this->concierge);
        $booking->update(['status' => BookingStatus::CONFIRMED]);
        $this->calculationService->calculateEarnings($booking);

        // Verify initial partner earnings exist for non-prime booking
        $initialPartnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($initialPartnerEarnings)->toBeGreaterThan(0);

        // Run the action
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        $booking->refresh();

        // Verify partner earnings are zeroed
        $newPartnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($newPartnerEarnings)->toBe(0);

        expect($result['bookings_recalculated'])->toBe(1);
    });
});

test('skips bookings without partner earnings', function () {
    Booking::withoutEvents(function () {
        // Clear all bookings to ensure clean test
        Booking::query()->delete();

        // Create booking without any partner associations
        $this->venue->user->update(['partner_referral_id' => null]);
        $this->concierge->user->update(['partner_referral_id' => null]);

        $booking = createBooking($this->venue, $this->concierge);
        $booking->update([
            'status' => BookingStatus::CONFIRMED, // Make it active so it would be found if it had partner earnings
            'partner_concierge_id' => null, // Explicitly remove partner association
            'partner_venue_id' => null, // Explicitly remove partner association
            'partner_concierge_fee' => 0, // Ensure no partner fees
            'partner_venue_fee' => 0, // Ensure no partner fees
        ]);
        $this->calculationService->calculateEarnings($booking);

        // Verify no partner earnings exist
        $partnerEarnings = $booking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($partnerEarnings)->toBe(0);

        // Run the action
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        // Should find no bookings to recalculate (active or inactive) since there are no partner associations
        expect($result['bookings_found'])->toBe(0);
        expect($result['bookings_recalculated'])->toBe(0);
        expect($result['inactive_bookings_found'])->toBe(0);
    });
});

// Note: Error handling test removed due to readonly class mocking limitations

test('getDryRunSummary returns accurate preview data', function () {
    Booking::withoutEvents(function () {
        // Create multiple bookings with partner earnings
        $booking1 = createBooking($this->venue, $this->concierge);
        $booking1->update(['status' => BookingStatus::CONFIRMED]);
        $booking2 = createNonPrimeBooking($this->venue, $this->concierge);
        $booking2->update(['status' => BookingStatus::CONFIRMED]);

        $this->calculationService->calculateEarnings($booking1);
        $this->calculationService->calculateEarnings($booking2);

        // Get dry run summary
        $summary = app(SetPartnerRevenueToZeroAndRecalculate::class)->getDryRunSummary();

        expect($summary['partners_to_update'])->toBe(12); // 10 seeded + partnerWithEarnings + partnerWithoutBookings
        expect($summary['bookings_to_recalculate'])->toBe(2);
        expect($summary['estimated_partner_earnings_to_zero'])->toBeGreaterThan(0);

        // Verify partner details
        expect($summary['partner_details'])->toHaveCount(12);
        $partnerIds = $summary['partner_details']->pluck('id')->toArray();
        expect($partnerIds)->toContain($this->partnerWithEarnings->id);
        expect($partnerIds)->toContain($this->partnerWithoutBookings->id);
        expect($partnerIds)->not->toContain($this->partnerWithZeroPercent->id); // Should not include 0% partners
    });
});

test('logs activity for partner percentage updates', function () {
    // Enable activity logging
    config(['activitylog.enabled' => true]);

    // Run the action
    SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

    // Verify activity was logged
    $activities = \Spatie\Activitylog\Models\Activity::where('description', 'Bulk updated partner percentages to 0')->get();
    expect($activities)->toHaveCount(1);

    $activity = $activities->first();
    expect($activity->properties['partner_ids'])->toContain($this->partnerWithEarnings->id);
    expect($activity->properties['previous_percentages'])->toHaveKey((string) $this->partnerWithEarnings->id);
});

test('logs activity for booking recalculations', function () {
    Booking::withoutEvents(function () {
        // Enable activity logging
        config(['activitylog.enabled' => true]);

        $booking = createBooking($this->venue, $this->concierge);
        $booking->update(['status' => BookingStatus::CONFIRMED]);
        $this->calculationService->calculateEarnings($booking);

        // Run the action
        SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        // Verify booking recalculation was logged
        $activities = \Spatie\Activitylog\Models\Activity::where('description', 'Booking recalculated after partner revenue set to zero')->get();
        expect($activities)->toHaveCount(1);

        $activity = $activities->first();
        expect($activity->subject_id)->toBe($booking->id);
        expect($activity->properties)->toHaveKeys(['original_partner_earnings', 'new_partner_earnings']);
    });
});

test('processes large number of bookings in chunks', function () {
    Booking::withoutEvents(function () {
        // Create multiple bookings (more than chunk size of 100)
        $bookings = collect();
        for ($i = 0; $i < 150; $i++) {
            $booking = createBooking($this->venue, $this->concierge);
            $booking->update(['status' => BookingStatus::CONFIRMED]);
            $this->calculationService->calculateEarnings($booking);
            $bookings->push($booking);
        }

        // Run the action
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        // Verify all bookings were processed
        expect($result['bookings_found'])->toBe(150);
        expect($result['bookings_recalculated'])->toBe(150);
        expect($result['errors'])->toBeEmpty();

        // Spot check that earnings were actually recalculated
        $randomBooking = $bookings->random();
        $partnerEarnings = $randomBooking->fresh()->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($partnerEarnings)->toBe(0);
    });
});

test('handles inactive bookings by zeroing partner fields only', function () {
    Booking::withoutEvents(function () {
        // Clear any existing bookings and earnings to ensure clean test
        Booking::query()->delete();
        Earning::query()->delete();

        // Create an active booking with partner earnings
        $activeBooking = createBooking($this->venue, $this->concierge);
        $activeBooking->update(['status' => BookingStatus::CONFIRMED]);
        $this->calculationService->calculateEarnings($activeBooking);

        // Create an inactive booking with partner associations but no earnings calculation
        $inactiveBooking = createBooking($this->venue, $this->concierge);
        $inactiveBooking->update([
            'status' => BookingStatus::CANCELLED,
            'partner_venue_id' => $this->partnerWithEarnings->id,
            'partner_concierge_id' => $this->partnerWithEarnings->id,
        ]);

        // Run the action
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

        // Verify statistics include both active and inactive bookings
        expect($result['bookings_found'])->toBe(1); // Only active bookings counted here
        expect($result['bookings_recalculated'])->toBe(2); // Total processed (1 active + 1 inactive)
        expect($result['inactive_bookings_found'])->toBe(1); // New statistic for inactive bookings
        expect($result['errors'])->toBeEmpty();

        // Verify inactive booking had partner fee fields zeroed (but IDs remain)
        $inactiveBooking->refresh();
        expect($inactiveBooking->partner_venue_id)->toBe($this->partnerWithEarnings->id); // ID should remain
        expect($inactiveBooking->partner_concierge_id)->toBe($this->partnerWithEarnings->id); // ID should remain
        expect($inactiveBooking->partner_venue_fee)->toBe(0); // Fee should be zeroed
        expect($inactiveBooking->partner_concierge_fee)->toBe(0); // Fee should be zeroed

        // Verify active booking was fully recalculated
        $activeBooking->refresh();
        $partnerEarnings = $activeBooking->earnings()
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->sum('amount');
        expect($partnerEarnings)->toBe(0);
    });
});

test('dry run shows inactive bookings statistics without making changes', function () {
    Booking::withoutEvents(function () {
        // Create an inactive booking with partner associations
        $inactiveBooking = createBooking($this->venue, $this->concierge);
        $inactiveBooking->update([
            'status' => BookingStatus::NO_SHOW,
            'partner_venue_id' => $this->partnerWithEarnings->id,
            'partner_concierge_id' => $this->partnerWithEarnings->id,
        ]);

        // Store original values
        $originalPartnerVenueId = $inactiveBooking->partner_venue_id;
        $originalPartnerConciergeId = $inactiveBooking->partner_concierge_id;

        // Run in dry-run mode
        $result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: true);

        // Verify statistics show inactive booking
        expect($result['dry_run'])->toBeTrue();
        expect($result['inactive_bookings_found'])->toBe(1);
        expect($result['bookings_found'])->toBe(0); // No active bookings

        // Verify no changes were made in dry-run
        $inactiveBooking->refresh();
        expect($inactiveBooking->partner_venue_id)->toBe($originalPartnerVenueId);
        expect($inactiveBooking->partner_concierge_id)->toBe($originalPartnerConciergeId);
    });
});
