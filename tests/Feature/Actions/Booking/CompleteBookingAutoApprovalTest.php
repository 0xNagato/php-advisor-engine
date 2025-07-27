<?php

use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\VenuePlatform;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function () {
    Event::fake();
    
    $this->venue = Venue::factory()->create();
    $this->scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
    ]);
});

it('skips regular confirmation SMS for auto-approval eligible bookings in CompleteBooking', function () {
    // Create venue with enabled platform
    VenuePlatform::factory()->restoo()->create([
        'venue_id' => $this->venue->id,
        'is_enabled' => true,
    ]);

    // Create small party booking
    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 6, // ≤7 guests
        'status' => 'pending',
        'total_fee' => 6000,
        'booking_at' => now()->addDay(),
    ]);

    // Mock SendConfirmationToVenueContacts to ensure it's NOT called  
    // SMS will be handled by the platform sync listener after platform sync
    $this->mock(SendConfirmationToVenueContacts::class)
        ->shouldNotReceive('handle');

    // Complete booking (non-prime, no payment intent needed)
    $result = CompleteBooking::run($booking, '', [
        'firstName' => 'John',
        'lastName' => 'Doe', 
        'phone' => '+1234567890',
        'email' => 'john@example.com',
        'r' => 'organic',
    ]);

    expect($result['success'])->toBeTrue();
});

it('sends regular confirmation SMS for non-eligible bookings in CompleteBooking', function () {
    // Create venue with disabled platform
    VenuePlatform::factory()->restoo()->disabled()->create([
        'venue_id' => $this->venue->id,
    ]);

    // Create booking
    $booking = Booking::factory()->create([
        'uuid' => Str::uuid(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 3, // ≤7 guests but no enabled platform
        'status' => 'pending',
        'total_fee' => 3000,
        'booking_at' => now()->addDay(),
    ]);

    // Mock SendConfirmationToVenueContacts to expect it's called once
    $this->mock(SendConfirmationToVenueContacts::class)
        ->shouldReceive('handle')
        ->once()
        ->with($booking);

    // Complete booking (non-prime, no payment intent needed)
    $result = CompleteBooking::run($booking, '', [
        'firstName' => 'Jane',
        'lastName' => 'Smith', 
        'phone' => '+1234567890',
        'email' => 'jane@example.com',
        'r' => 'organic',
    ]);

    expect($result['success'])->toBeTrue();
});