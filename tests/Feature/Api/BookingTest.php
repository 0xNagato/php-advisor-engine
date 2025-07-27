<?php

use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueTimeSlot;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Stripe\StripeClient;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    // Mock StripeClient for payment intent creation
    $this->stripeMock = Mockery::mock(StripeClient::class);
    $this->stripeMock->paymentIntents = Mockery::mock();

    // Set up default payment intent mock response
    $defaultResponse = new class
    {
        public string $client_secret = 'pi_test_1234567890_secret_abcdefghij';

        public string $id = 'pi_test_1234567890';

        public int $amount = 15000;

        public string $currency = 'usd';

        public string $status = 'requires_payment_method';
    };

    $this->stripeMock->paymentIntents->shouldReceive('create')
        ->withAnyArgs()
        ->andReturn($defaultResponse);

    app()->instance(StripeClient::class, $this->stripeMock);

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

    // Mock external services to prevent side effects during API tests
    // Do this after venue/template setup is complete
    Event::fake([
        \App\Events\BookingPaid::class,
        \App\Events\BookingConfirmed::class,
    ]);
    Notification::fake();
    Storage::fake('local');
    Queue::fake();
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

test('prime booking includes payment intent secret', function () {
    // Create a prime schedule template
    $primeScheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => '20:00:00',
        'party_size' => 2,
        'prime_time' => true, // This is what makes it prime
        'day_of_week' => 1, // Monday
    ]);

    $response = postJson('/api/bookings', [
        'date' => now()->addDay()->format('Y-m-d'),
        'schedule_template_id' => $primeScheduleTemplate->id,
        'guest_count' => 2,
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'bookings_enabled',
                'bookings_disabled_message',
                'id',
                'guest_count',
                'status',
                'is_prime',
                'paymentIntentSecret', // Should be present for prime bookings
            ],
        ])
        ->assertJson([
            'data' => [
                'is_prime' => 'true',
            ],
        ]);

    expect($response->json('data.paymentIntentSecret'))->toEqual('pi_test_1234567890_secret_abcdefghij');
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
        'phone' => '+120155564'.str_pad(random_int(10, 99), 2, '0', STR_PAD_LEFT),
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

afterEach(function () {
    Mockery::close();
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

test('cannot update conflicting non-prime booking within 2-hour window', function () {
    // Generate unique phone number for this test to avoid parallel test conflicts
    $uniquePhone = '+120155564'.str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
    $uniqueEmail = 'john'.random_int(10000, 99999).'@example.com';

    // Create a non-prime schedule template for 6 PM
    $firstTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'prime_time' => false,
        'start_time' => '18:00:00',
    ]);

    // Create second template for 7 PM (1 hour later, within 2-hour window)
    $secondTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'prime_time' => false,
        'start_time' => '19:00:00',
    ]);

    $bookingDate = now()->addDays(2)->format('Y-m-d');

    // Create and set up first booking directly in database to avoid HTTP timing issues
    // This eliminates race conditions from sequential HTTP requests in parallel tests
    $firstBooking = \App\Models\Booking::factory()->create([
        'schedule_template_id' => $firstTemplate->id,
        'guest_phone' => $uniquePhone,
        'guest_email' => $uniqueEmail,
        'guest_first_name' => 'John',
        'guest_last_name' => 'Doe',
        'booking_at' => $bookingDate.' 18:00:00',
        'is_prime' => false,
        'status' => \App\Enums\BookingStatus::PENDING,
        'concierge_id' => $this->concierge->id,
    ]);

    // Verify the booking was created with the expected phone number
    expect($firstBooking->guest_phone)->toBe($uniquePhone);

    // Create second booking directly in database to avoid race conditions
    $secondBooking = \App\Models\Booking::factory()->create([
        'schedule_template_id' => $secondTemplate->id,
        'guest_phone' => '+1234567890', // Different phone initially
        'guest_email' => 'different@test.com',
        'guest_first_name' => 'Jane',
        'guest_last_name' => 'Smith',
        'booking_at' => $bookingDate.' 19:00:00',
        'is_prime' => false,
        'status' => \App\Enums\BookingStatus::PENDING,
        'concierge_id' => $this->concierge->id,
        'guest_count' => 2,
    ]);

    // Try to update second booking with same phone number (should be blocked)
    putJson("/api/bookings/{$secondBooking->id}", [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => $uniquePhone, // Same phone number as first booking
        'email' => $uniqueEmail,
        'notes' => '',
        'bookingUrl' => 'https://example.com',
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', function ($message) {
            return str_contains($message, 'You already have a no‑fee booking on') &&
                   str_contains($message, 'Bookings must be at least 2 hours apart');
        });
});

