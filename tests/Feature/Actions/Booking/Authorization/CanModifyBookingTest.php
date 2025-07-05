<?php

use App\Actions\Booking\Authorization\CanModifyBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Set up any required state before each test
    Carbon::setTestNow(now());
});

it('allows modification for null user when booking is valid', function () {
    $booking = Booking::factory()->forVenue()->create([
        'status' => BookingStatus::CONFIRMED,
        'is_prime' => false,
        'booking_at' => now()->addHour()->format('Y-m-d H:i:s'),
    ]);

    expect(CanModifyBooking::run($booking, null))->toBeTrue();
});

it('denies modification for cancelled booking even if user is null', function () {
    $booking = Booking::factory()->forVenue()->create([
        'status' => BookingStatus::CANCELLED,
        'is_prime' => false,
        'booking_at' => now()->addHour()->format('Y-m-d H:i:s'),
    ]);

    expect(CanModifyBooking::run($booking, null))->toBeFalse();
});

it('allows super admin to modify booking', function () {
    $user = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->forVenue()->create([
        'status' => BookingStatus::CONFIRMED,
        'is_prime' => false,
        'booking_at' => now()->addHour()->format('Y-m-d H:i:s'),
    ]);

    expect(CanModifyBooking::run($booking, $user))->toBeTrue();
});

it('denies regular user to modify booking', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->forVenue()->create([
        'status' => BookingStatus::CONFIRMED,
        'is_prime' => false,
        'booking_at' => now()->addHour()->format('Y-m-d H:i:s'),
    ]);

    expect(CanModifyBooking::run($booking, $user))->toBeFalse();
});
