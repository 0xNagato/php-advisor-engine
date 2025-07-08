<?php

use App\Actions\Booking\Authorization\CanModifyBooking;
use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

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
    $this->scheduleTemplate->update(['prime_time' => false]);

    $this->action = new CreateBooking;
    actingAs($this->concierge->user);
});

it('allows modification for null user when booking is valid', function () {
    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData
    );

    $booking = $result->booking;
    $booking->update([
        'status' => BookingStatus::CONFIRMED,
    ]);

    expect(CanModifyBooking::run($booking, null))->toBeTrue();
});

it('denies modification for cancelled booking even if user is null', function () {
    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData
    );

    $booking = $result->booking;
    $booking->update([
        'status' => BookingStatus::CANCELLED,
    ]);

    expect(CanModifyBooking::run($booking, null))->toBeFalse();
});

it('allows super admin to modify booking', function () {
    $user = User::role('super_admin')->first();

    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData
    );

    $booking = $result->booking;
    $booking->update([
        'status' => BookingStatus::CONFIRMED,
    ]);

    expect(CanModifyBooking::run($booking, $user))->toBeTrue();
});

it('denies regular user to modify booking', function () {
    $user = User::factory()->create();
    $this->scheduleTemplate->update(['prime_time' => false]);

    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData
    );

    $booking = $result->booking;
    $booking->update([
        'status' => BookingStatus::CONFIRMED,
    ]);

    expect(CanModifyBooking::run($booking, $user))->toBeFalse();
});
