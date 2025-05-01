<?php

namespace Tests\Services;

use App\Enums\VenueStatus;
use App\Models\Region;
use App\Models\Venue;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

beforeEach(function () {
    // Retrieve a valid Region using Eloquent methods
    $this->region = Region::query()->first(); // Fetch the first region from Sushi model

    // Shared date setup for consistency in test scenarios
    $this->today = Carbon::now()->format('Y-m-d');
    $this->tomorrow = Carbon::now()->addDay()->format('Y-m-d');
    $this->dayAfterTomorrow = Carbon::now()->addDays(2)->format('Y-m-d');
});

test('it includes venues with advance_booking_window = 0 for any day', function () {
    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 0, // Always available for any day
        'status' => VenueStatus::ACTIVE,
    ]);

    $service = new ReservationService(
        date: $this->today,
        guestCount: 4,
        reservationTime: '18:00:00',
        region: $this->region
    );

    $venues = $service->getAvailableVenues();

    expect($venues)->toBeInstanceOf(Collection::class)
        ->and($venues->count())->toBe(1)
        ->and($venues->first()->advance_booking_window)->toBe(0);
});

// Test with advance_booking_window matching $dayDifference
test('it includes venues with advance_booking_window matching dayDifference', function () {
    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 1,
        'status' => VenueStatus::ACTIVE,
    ]);

    $service = new ReservationService(
        date: $this->today,
        guestCount: 4,
        reservationTime: '18:00:00',
        region: $this->region
    );

    $venues = $service->getAvailableVenues();

    expect($venues->count())->toBe(1)
        ->and($venues->first()->advance_booking_window)->toBe(1);

    // Now for tomorrow's reservation, it should fail
    $service = new ReservationService(
        date: $this->tomorrow, // Tomorrow makes $dayDifference = 2
        guestCount: 4,
        reservationTime: '18:00:00',
        region: $this->region
    );

    $venues = $service->getAvailableVenues();

    expect($venues->count())->toBe(0); // It shouldn't return
});

// Test with advance_booking_window > $dayDifference
test('it includes venues with advance_booking_window greater than dayDifference', function () {
    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 3, // Available for future dates
        'status' => VenueStatus::ACTIVE,
    ]);

    $service = new ReservationService(
        date: $this->dayAfterTomorrow,
        guestCount: 4,
        reservationTime: '18:00:00',
        region: $this->region
    );

    $venues = $service->getAvailableVenues();

    expect($venues->count())->toBe(1)
        ->and($venues->first()->advance_booking_window)->toBe(3);
});

// Test with status filtering
test('it excludes venues with invalid status during filtering', function () {
    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 0,
        'status' => VenueStatus::DRAFT, // Invalid status
    ]);

    $service = new ReservationService(
        date: $this->today,
        guestCount: 2,
        reservationTime: '19:00:00',
        region: $this->region
    );

    $venues = $service->getAvailableVenues();

    expect($venues->count())->toBe(0); // No venues due to invalid status
});

// Test with multiple venues having different advance_booking_window settings
test('it correctly filters multiple venues with different advance_booking_window settings', function () {
    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 0,
        'status' => VenueStatus::ACTIVE,
    ]);

    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 1,
        'status' => VenueStatus::ACTIVE,
    ]);

    Venue::factory()->create([
        'region' => $this->region->id,
        'advance_booking_window' => 2,
        'status' => VenueStatus::ACTIVE,
    ]);

    $service = new ReservationService(
        date: $this->today,
        guestCount: 2,
        reservationTime: '19:00:00',
        region: $this->region
    );

    $venuesToday = $service->getAvailableVenues();

    // Should include all venues on the first day
    expect($venuesToday->count())->toBe(3);

    // For tomorrow's check
    $serviceTomorrow = new ReservationService(
        date: $this->tomorrow, // Tomorrow
        guestCount: 2,
        reservationTime: '19:00:00',
        region: $this->region
    );

    $venuesTomorrow = $serviceTomorrow->getAvailableVenues();

    // Should exclude the venue with advance_booking_window = 1
    expect($venuesTomorrow->count())->toBe(2);
});

test('it returns available venues with valid data', function () {
    $region = Region::first();
    Venue::factory()->count(2)->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);

    $service = new ReservationService(
        date: now()->format('Y-m-d'),
        guestCount: 4,
        reservationTime: '18:00:00',
        region: $region
    );

    $venues = $service->getAvailableVenues();

    expect($venues)->toBeInstanceOf(Collection::class)
        ->and($venues->isNotEmpty())->toBeTrue('Expected at least one venue to be returned');
});

test('it excludes venues in a different region', function () {
    $region = Region::first();
    $otherRegion = Region::latest()->first();
    Venue::factory()->create(['region' => $otherRegion->id]);

    $service = new ReservationService(
        date: now()->format('Y-m-d'),
        guestCount: 2,
        reservationTime: '19:00:00',
        region: $region
    );

    $venues = $service->getAvailableVenues();

    expect($venues->isEmpty())->toBeTrue('Expected no venues from other regions to be returned');
});

test('it filters venues by status', function () {
    $region = Region::first();
    Venue::factory()->create(['region' => $region->id, 'status' => VenueStatus::DRAFT]);

    $service = new ReservationService(
        date: now()->format('Y-m-d'),
        guestCount: 2,
        reservationTime: '20:00:00',
        region: $region
    );

    $venues = $service->getAvailableVenues();

    expect($venues->isEmpty())->toBeTrue('Expected venues with DRAFT status to be excluded');
});
