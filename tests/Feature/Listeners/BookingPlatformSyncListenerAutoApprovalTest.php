<?php

use App\Actions\Booking\AutoApproveSmallPartyBooking;
use App\Events\BookingConfirmed;
use App\Listeners\BookingPlatformSyncListener;
use App\Models\Booking;
use App\Models\PlatformReservation;
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

    // Clean up any existing platform reservations for this booking
    PlatformReservation::where('booking_id', $this->booking->id)->delete();

    // Helper method to call private methods
    $this->callPrivateMethod = function ($object, $method, $parameters = []) {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    };
});

it('triggers auto-approval after successful platform sync', function () {
    // Create enabled platform
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    // Create a platform reservation that will sync successfully
    PlatformReservation::factory()->create([
        'venue_id' => $this->venue->id,
        'booking_id' => $this->booking->id,
        'platform_type' => 'restoo',
        'synced_to_platform' => true,
        'platform_reservation_id' => 'test-reservation-123',
    ]);

    // Mock the auto-approval action using Laravel Actions mocking
    AutoApproveSmallPartyBooking::shouldRun()
        ->with($this->booking)
        ->andReturn(true);

    // Verify the platform reservation exists and is synced
    $reservation = PlatformReservation::where('booking_id', $this->booking->id)->first();
    expect($reservation)->not->toBeNull();
    expect($reservation->synced_to_platform)->toBeTrue();

    $listener = app(BookingPlatformSyncListener::class);
    $event = new BookingConfirmed($this->booking);

    $listener->handle($event);
});

it('does not trigger auto-approval when platform sync fails', function () {
    // Create enabled platform
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    // Mock Restoo service to return failure for this test
    $this->mock(\App\Services\RestooService::class, function ($mock) {
        $mock->shouldReceive('createReservation')
             ->andReturn(null); // Return null to simulate failure
    });

    // Use Laravel Actions mocking to verify the action is not called
    AutoApproveSmallPartyBooking::shouldNotRun();

    $listener = app(BookingPlatformSyncListener::class);
    $event = new BookingConfirmed($this->booking);

    $listener->handle($event);
});

it('logs auto-approval success', function () {
    VenuePlatform::factory()->create([
        'venue_id' => $this->venue->id,
        'platform_type' => 'restoo',
        'is_enabled' => true,
    ]);

    // Create a platform reservation that will sync successfully
    PlatformReservation::factory()->create([
        'venue_id' => $this->venue->id,
        'booking_id' => $this->booking->id,
        'platform_type' => 'restoo',
        'synced_to_platform' => true,
        'platform_reservation_id' => 'test-reservation-456',
    ]);

    // Mock successful auto-approval using Laravel Actions mocking
    AutoApproveSmallPartyBooking::shouldRun()
        ->with($this->booking)
        ->andReturn(true);

    // Expect success log
    Log::shouldReceive('info')
        ->once()
        ->with("Booking {$this->booking->id} was auto-approved after successful platform sync");

    $listener = app(BookingPlatformSyncListener::class);
    $event = new BookingConfirmed($this->booking);

    $listener->handle($event);
});

it('does not trigger auto-approval when venue has no platforms', function () {
    // No platforms for venue

    // Use Laravel Actions mocking to verify the action is not called
    AutoApproveSmallPartyBooking::shouldNotRun();

    $listener = app(BookingPlatformSyncListener::class);
    $event = new BookingConfirmed($this->booking);

    $listener->handle($event);
});
