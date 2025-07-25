<?php

use App\Actions\Booking\CheckCustomerHasConflictingNonPrimeBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use Carbon\Carbon;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);

    $this->concierge = Concierge::factory()->create();
    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'prime_time' => false,
    ]);

    $this->phoneNumber = '+1234567890';
    $this->action = new CheckCustomerHasConflictingNonPrimeBooking;

    // Get the formatted phone number that the action will use
    $this->formattedPhoneNumber = $this->action->getInternationalFormattedPhoneNumber($this->phoneNumber);
});

it('returns null when no existing bookings', function () {
    $bookingTime = Carbon::now()->addDays(2);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('finds conflicting booking within 2-hour window (1 hour before)', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create existing booking 1 hour before
    $existingBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($existingBooking->id);
});

it('finds conflicting booking within 2-hour window (1 hour after)', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create existing booking 1 hour after
    $existingBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->addHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($existingBooking->id);
});

it('finds conflicting booking exactly at 2-hour boundary', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create existing booking exactly 2 hours before
    $existingBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHours(2),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($existingBooking->id);
});

it('does not find booking outside 2-hour window (3 hours before)', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create existing booking 3 hours before (outside window)
    Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHours(3),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('does not find booking outside 2-hour window (3 hours after)', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create existing booking 3 hours after (outside window)
    Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->addHours(3),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('ignores prime bookings when checking for conflicts', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create existing PRIME booking within window
    Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => true, // This is prime, should be ignored
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('ignores cancelled bookings when checking for conflicts', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create cancelled booking within window
    Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CANCELLED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('ignores refunded bookings when checking for conflicts', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create refunded booking within window
    Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::REFUNDED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('ignores abandoned bookings when checking for conflicts', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create abandoned booking within window
    Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::ABANDONED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('only finds bookings for the same phone number', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create booking with different phone number
    Booking::factory()->create([
        'guest_phone' => '+9876543210', // Different phone number
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->toBeNull();
});

it('works across different dates within time window', function () {
    // Booking at 1 AM tomorrow
    $bookingTime = Carbon::now()->addDay()->setTime(1, 0);

    // Existing booking at 11 PM today (2 hours before)
    $existingBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => Carbon::now()->setTime(23, 0), // Today at 11 PM
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($existingBooking->id);
});

it('finds the first conflicting booking when multiple exist', function () {
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create two conflicting bookings
    $firstBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
        'created_at' => now()->subHours(2),
    ]);

    $secondBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->addHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
        'created_at' => now()->subHour(),
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBeIn([$firstBooking->id, $secondBooking->id]);
});

it('respects the BOOKING_WINDOW_HOURS constant', function () {
    // Temporarily change the constant by testing at the exact boundary
    $windowHours = CheckCustomerHasConflictingNonPrimeBooking::BOOKING_WINDOW_HOURS;
    $bookingTime = Carbon::now()->addDays(2)->setTime(18, 0);

    // Create booking exactly at the window boundary
    $existingBooking = Booking::factory()->create([
        'guest_phone' => $this->formattedPhoneNumber,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHours($windowHours),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    // Should find the booking at exactly the boundary
    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($existingBooking->id);

    // Test just outside the boundary
    $existingBooking->update([
        'booking_at' => $bookingTime->copy()->subHours($windowHours + 0.1), // Just outside
    ]);

    $result = $this->action->handle($this->phoneNumber, $bookingTime);

    // Should NOT find the booking just outside the boundary
    expect($result)->toBeNull();
});
