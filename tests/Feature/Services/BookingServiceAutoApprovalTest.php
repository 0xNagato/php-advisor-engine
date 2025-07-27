<?php

use App\Actions\Booking\AutoApproveSmallPartyBooking;
use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\VenuePlatform;
use App\Services\BookingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function () {
    Event::fake();
    
    $this->venue = Venue::factory()->create();
    $this->scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
    ]);
});

it('skips regular confirmation SMS for auto-approval eligible bookings', function () {
    // Create venue with enabled platform
    VenuePlatform::factory()->covermanager()->create([
        'venue_id' => $this->venue->id,
        'is_enabled' => true,
    ]);

    // Create small party booking (≤7 guests)
    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 4, // ≤7 guests
        'status' => 'confirmed',
        'total_fee' => 5000,
        'booking_at' => now()->addDay(),
    ]);

    // Mock SendConfirmationToVenueContacts to ensure it's NOT called
    // SMS will be handled by the platform sync listener after platform sync
    $this->mock(SendConfirmationToVenueContacts::class)
        ->shouldNotReceive('handle');

    // Process booking
    $bookingService = new BookingService();
    $bookingService->processBooking($booking, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+1234567890',
        'email' => 'john@example.com',
    ]);
});

it('sends regular confirmation SMS for non-auto-approval eligible bookings', function () {
    // Create venue WITHOUT enabled platform
    VenuePlatform::factory()->covermanager()->disabled()->create([
        'venue_id' => $this->venue->id,
    ]);

    // Create booking
    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 4,
        'status' => 'confirmed',
        'total_fee' => 5000,
        'booking_at' => now()->addDay(),
    ]);

    // Debug: Check if booking qualifies for auto-approval
    $qualifies = \App\Actions\Booking\AutoApproveSmallPartyBooking::qualifiesForAutoApproval($booking);
    expect($qualifies)->toBeFalse('Booking should NOT qualify for auto-approval');

    // Mock SendConfirmationToVenueContacts to expect it's called once
    $this->mock(SendConfirmationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once()
        ->with($booking);

    // Process booking
    $bookingService = new BookingService();
    $bookingService->processBooking($booking, [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'phone' => '+1234567890',
        'email' => 'jane@example.com',
    ]);
});

it('sends regular confirmation SMS for large party bookings', function () {
    // Create venue with enabled platform
    VenuePlatform::factory()->covermanager()->create([
        'venue_id' => $this->venue->id,
        'is_enabled' => true,
    ]);

    // Create large party booking (>7 guests)
    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 10, // >7 guests
        'status' => 'confirmed',
        'total_fee' => 8000,
        'booking_at' => now()->addDay(),
    ]);

    // Mock SendConfirmationToVenueContacts to expect it's called once
    $this->mock(SendConfirmationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once()
        ->with($booking);

    // Process booking
    $bookingService = new BookingService();
    $bookingService->processBooking($booking, [
        'first_name' => 'Bob',
        'last_name' => 'Johnson',
        'phone' => '+1234567890',
        'email' => 'bob@example.com',
    ]);
});

it('qualifiesForAutoApproval returns true for eligible bookings', function () {
    // Create venue with enabled platform
    VenuePlatform::factory()->covermanager()->create([
        'venue_id' => $this->venue->id,
        'is_enabled' => true,
    ]);

    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 5, // ≤7 guests
        'total_fee' => 5000,
        'booking_at' => now()->addDay(),
    ]);

    expect(AutoApproveSmallPartyBooking::qualifiesForAutoApproval($booking))->toBeTrue();
});

it('qualifiesForAutoApproval returns false for large party bookings', function () {
    // Create venue with enabled platform
    VenuePlatform::factory()->covermanager()->create([
        'venue_id' => $this->venue->id,
        'is_enabled' => true,
    ]);

    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 10, // >7 guests
        'total_fee' => 8000,
        'booking_at' => now()->addDay(),
    ]);

    expect(AutoApproveSmallPartyBooking::qualifiesForAutoApproval($booking))->toBeFalse();
});

it('qualifiesForAutoApproval returns false for venues without enabled platforms', function () {
    // Create venue WITHOUT enabled platforms
    VenuePlatform::factory()->covermanager()->disabled()->create([
        'venue_id' => $this->venue->id,
    ]);

    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 4, // ≤7 guests
        'total_fee' => 5000,
        'booking_at' => now()->addDay(),
    ]);

    expect(AutoApproveSmallPartyBooking::qualifiesForAutoApproval($booking))->toBeFalse();
});