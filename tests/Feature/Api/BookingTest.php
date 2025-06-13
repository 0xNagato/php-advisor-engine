<?php

use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueTimeSlot;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('concierge');
    $this->concierge = Concierge::factory()->create();
    $this->user = $this->concierge->user;

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;

    // Create a test venue
    $this->venue = Venue::factory()->create([
        'status' => VenueStatus::ACTIVE,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);

    // Get a base template (party_size = 0)
    $baseTemplate = ScheduleTemplate::where([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
        'party_size' => 0,
    ])->get()->first();

    // Get a guest count template
    $this->scheduleTemplate = ScheduleTemplate::where([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
        'day_of_week' => $baseTemplate->day_of_week,
        'party_size' => 2,
    ])->get()->first();

    // Create test timeslot
    $this->timeslot = VenueTimeSlot::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'booking_date' => now(),
        'prime_time' => false,
        'is_available' => true,
        'available_tables' => 1,
    ]);

    // Create a test booking
    $this->booking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'booking_at' => now(),
        'status' => 'confirmed',
    ]);
});

test('unauthenticated user cannot create booking', function () {
    postJson('/api/bookings', [
        'venue_id' => $this->venue->id,
        'time_slot_id' => $this->timeslot->id,
    ])
        ->assertUnauthorized();
});

test('authenticated user can create booking', function () {
    postJson('/api/bookings', [
        'date' => now()->addDay()->format('Y-m-d'),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 2,
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'bookings_enabled',
                'bookings_disabled_message',
                'id',
                'guest_count',
                'status',
            ],
        ]);
});

test('booking require date', function () {
    postJson('/api/bookings', [
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 2,
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422);
});

test('user can confirm their booking', function () {
    // Create a test booking
    $booking = Booking::factory()->create([
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'booking_at' => now(),
        'status' => BookingStatus::PENDING,
    ]);
    putJson("/api/bookings/$booking->id", [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+12015556478',
        'bookingUrl' => 'https://www.google.com',
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::CONFIRMED);
});

test('user can delete their booking', function () {
    // Create a test booking
    $booking = Booking::factory()->create([
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'booking_at' => now(),
        'status' => BookingStatus::PENDING,
    ]);
    deleteJson("/api/bookings/{$booking->id}", [], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Booking Abandoned',
        ]);

    expect($booking->fresh()->status)->toBe(BookingStatus::ABANDONED);
});

test('user cannot delete booking on status confirmed', function () {
    // Create a test booking
    $booking = Booking::factory()->create([
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'booking_at' => now(),
        'status' => BookingStatus::CONFIRMED,
    ]);

    deleteJson("/api/bookings/$booking->id", [], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Booking cannot be abandoned in its current status',
        ]);
});
