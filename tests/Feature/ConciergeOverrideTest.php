<?php

use App\Actions\Booking\CheckIfConciergeCanOverrideDuplicateChecks;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);
    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'prime_time' => false,
    ]);

    // Create a concierge and associate it with a user
    $this->concierge = Concierge::factory()->create([
        'can_override_duplicate_checks' => true,
    ]);

    // Retrieve the user's phone for the concierge
    $this->conciergePhone = $this->concierge->user->phone ?? Str::random(10);

    // Initialize the action to check duplicate checks override
    $this->action = new CheckIfConciergeCanOverrideDuplicateChecks;
});

it('allows concierge to override duplicate checks when permitted', function () {
    // Define the booking time
    $bookingTime = Carbon::now()->addDays()->setTime(14, 0);

    $booking = Booking::factory()->create([
        'concierge_id' => $this->concierge->id,
        'guest_phone' => $this->conciergePhone,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Trigger the action to check for duplicate override
    $result = $this->action->handle($booking, $this->conciergePhone);

    // Assert that the result is true (override allowed)
    expect($result)->toBeTrue();
});

it('prevents concierge from overriding duplicate checks when not permitted', function () {
    // Update concierge to disable override permission
    $this->concierge->update(['can_override_duplicate_checks' => false]);

    // Define the booking time
    $bookingTime = Carbon::now()->addDays(1)->setTime(14, 0);

    // Create booking
    $booking = Booking::factory()->create([
        'concierge_id' => $this->concierge->id,
        'guest_phone' => $this->conciergePhone,
        'is_prime' => false,
        'booking_at' => $bookingTime->copy()->subHour(),
        'schedule_template_id' => $this->scheduleTemplate->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Trigger the action to check for duplicate override
    $result = $this->action->handle($booking, $this->conciergePhone);

    // Assert that the result is false (override not allowed)
    expect($result)->toBeFalse();
});
