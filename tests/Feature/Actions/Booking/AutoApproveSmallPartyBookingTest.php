<?php

use App\Actions\Booking\AutoApproveSmallPartyBooking;
use App\Actions\Booking\SendAutoApprovalNotificationToVenueContacts;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\VenuePlatform;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
    $this->scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
    ]);
    $this->booking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 5, // Small party
        'venue_confirmed_at' => null,
        'booking_at' => now()->addDay(),
    ]);
});

it('auto-approves booking when all conditions are met', function () {
    // Create enabled platform for venue
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    // Mock the notification action
    $this->mock(SendAutoApprovalNotificationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once()
        ->with($this->booking);

    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeTrue();
    expect($this->booking->fresh()->venue_confirmed_at)->not->toBeNull();
});

it('does not auto-approve when party size exceeds limit', function () {
    $largePartyBooking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 8, // Exceeds limit of 7
        'venue_confirmed_at' => null,
        'booking_at' => now()->addDay(),
    ]);

    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    $result = AutoApproveSmallPartyBooking::run($largePartyBooking);

    expect($result)->toBeFalse();
    expect($largePartyBooking->fresh()->venue_confirmed_at)->toBeNull();
});

it('auto-approves when party size is exactly at limit', function () {
    $limitPartyBooking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => AutoApproveSmallPartyBooking::MAX_AUTO_APPROVAL_PARTY_SIZE, // Exactly 7
        'venue_confirmed_at' => null,
        'booking_at' => now()->addDay(),
    ]);

    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    $this->mock(SendAutoApprovalNotificationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once();

    $result = AutoApproveSmallPartyBooking::run($limitPartyBooking);

    expect($result)->toBeTrue();
    expect($limitPartyBooking->fresh()->venue_confirmed_at)->not->toBeNull();
});

it('does not auto-approve when venue has no enabled platforms', function () {
    // No platforms for venue
    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeFalse();
    expect($this->booking->fresh()->venue_confirmed_at)->toBeNull();
});

it('does not auto-approve when venue platform is disabled', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => false, // Disabled
    ]);

    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeFalse();
    expect($this->booking->fresh()->venue_confirmed_at)->toBeNull();
});

it('does not auto-approve when venue has unsupported platform', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'unsupported_platform',
        'is_enabled' => true,
    ]);

    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeFalse();
    expect($this->booking->fresh()->venue_confirmed_at)->toBeNull();
});

it('auto-approves with covermanager platform', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'covermanager',
        'is_enabled' => true,
    ]);

    $this->mock(SendAutoApprovalNotificationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once();

    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeTrue();
    expect($this->booking->fresh()->venue_confirmed_at)->not->toBeNull();
});

it('does not auto-approve when booking is already venue confirmed', function () {
    $confirmedBooking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 5,
        'venue_confirmed_at' => now(),
        'booking_at' => now()->addDay(),
    ]);

    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    $result = AutoApproveSmallPartyBooking::run($confirmedBooking);

    expect($result)->toBeFalse();
});

it('auto-approves with multiple enabled platforms', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'covermanager',
        'is_enabled' => true,
    ]);

    $this->mock(SendAutoApprovalNotificationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once();

    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeTrue();
    expect($this->booking->fresh()->venue_confirmed_at)->not->toBeNull();
});

it('logs auto-approval activity', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    $this->mock(SendAutoApprovalNotificationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once();

    AutoApproveSmallPartyBooking::run($this->booking);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Booking::class,
        'subject_id' => $this->booking->id,
        'description' => 'Booking auto-approved for small party with platform integration',
    ]);
});

it('logs detailed information when auto-approving', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    $this->mock(SendAutoApprovalNotificationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once();

    Log::shouldReceive('debug')
        ->once()
        ->with(
            "Booking {$this->booking->id} qualifies for auto-approval",
            Mockery::type('array')
        );

    Log::shouldReceive('info')
        ->once()
        ->with(
            "Auto-approved booking {$this->booking->id} for {$this->booking->guest_count} guests at {$this->venue->name}",
            Mockery::type('array')
        );

    AutoApproveSmallPartyBooking::run($this->booking);
});

it('logs debug information when booking does not qualify', function () {
    // No platforms - should log debug info
    Log::shouldReceive('debug')
        ->once()
        ->with(
            "Booking {$this->booking->id} not auto-approved: venue has no enabled platforms (restoo/covermanager)"
        );

    // Should not log the specific auto-approval success message
    Log::shouldReceive('info')
        ->with("Auto-approved booking {$this->booking->id} for {$this->booking->guest_count} guests at {$this->venue->name}", Mockery::any())
        ->never();

    $result = AutoApproveSmallPartyBooking::run($this->booking);

    expect($result)->toBeFalse();
});
