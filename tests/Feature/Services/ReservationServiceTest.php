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

// TimeSlotOffset Tests
test('timeSlotOffset controls position of requested time in timeslot headers', function () {
    $region = Region::where('id', 'miami')->first(); // Use Miami for consistent timezone
    $requestTime = '14:30:00'; // 2:30 PM
    $date = '2025-06-21';
    $guestCount = 2;
    $timeslotCount = 5;

    // Test offset 0: requested time should be first slot
    $service0 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 0,
        region: $region
    );

    $headers0 = $service0->getTimeslotHeaders();
    expect($headers0)->toBe(['2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM'])
        ->and($headers0[0])->toBe('2:30 PM', 'With offset 0, requested time should be first slot');

    // Test offset 1: requested time should be second slot
    $service1 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 1,
        region: $region
    );

    $headers1 = $service1->getTimeslotHeaders();
    expect($headers1)->toBe(['2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM'])
        ->and($headers1[1])->toBe('2:30 PM', 'With offset 1, requested time should be second slot');

    // Test offset 2: requested time should be third slot (middle)
    $service2 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 2,
        region: $region
    );

    $headers2 = $service2->getTimeslotHeaders();
    expect($headers2)->toBe(['1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM'])
        ->and($headers2[2])->toBe('2:30 PM', 'With offset 2, requested time should be third slot (middle)');

    // Test offset 3: requested time should be fourth slot
    $service3 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 3,
        region: $region
    );

    $headers3 = $service3->getTimeslotHeaders();
    expect($headers3)->toBe(['1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM'])
        ->and($headers3[3])->toBe('2:30 PM', 'With offset 3, requested time should be fourth slot');

    // Test offset 4: requested time should be fifth slot (last)
    $service4 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 4,
        region: $region
    );

    $headers4 = $service4->getTimeslotHeaders();
    expect($headers4)->toBe(['12:30 PM', '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM'])
        ->and($headers4[4])->toBe('2:30 PM', 'With offset 4, requested time should be fifth slot (last)');
});

test('timeSlotOffset is capped by timeslot count minus one', function () {
    $region = Region::where('id', 'miami')->first();
    $requestTime = '14:30:00'; // 2:30 PM
    $date = '2025-06-21';
    $guestCount = 2;
    $timeslotCount = 3; // Only 3 slots

    // Test offset 5 (higher than timeslotCount - 1 = 2)
    // Should be capped at 2
    $service = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 5, // This should be capped to 2
        region: $region
    );

    $headers = $service->getTimeslotHeaders();
    expect($headers)->toBe(['1:30 PM', '2:00 PM', '2:30 PM'])
        ->and($headers[2])->toBe('2:30 PM', 'Offset should be capped at timeslotCount - 1');
});

test('timeSlotOffset works with different times', function () {
    $region = Region::where('id', 'miami')->first();
    $date = '2025-06-21';
    $guestCount = 2;
    $timeslotCount = 5;
    $offset = 2; // Middle position

    // Test with 7:00 PM
    $service1 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: '19:00:00', // 7:00 PM
        timeslotCount: $timeslotCount,
        timeSlotOffset: $offset,
        region: $region
    );

    $headers1 = $service1->getTimeslotHeaders();
    expect($headers1)->toBe(['6:00 PM', '6:30 PM', '7:00 PM', '7:30 PM', '8:00 PM'])
        ->and($headers1[2])->toBe('7:00 PM', '7:00 PM should be in middle position');

    // Test with 12:00 PM (noon)
    $service2 = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: '12:00:00', // 12:00 PM
        timeslotCount: $timeslotCount,
        timeSlotOffset: $offset,
        region: $region
    );

    $headers2 = $service2->getTimeslotHeaders();
    expect($headers2)->toBe(['11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM', '1:00 PM'])
        ->and($headers2[2])->toBe('12:00 PM', '12:00 PM should be in middle position');
});

test('vip calendar behavior matches offset 2', function () {
    // This test verifies that offset 2 provides the "centered" behavior
    // that's used in the VIP calendar
    $region = Region::where('id', 'miami')->first();
    $requestTime = '14:30:00'; // 2:30 PM
    $date = '2025-06-21';
    $guestCount = 2;
    $timeslotCount = 5;

    $service = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 2, // This is what VIP calendar uses
        region: $region
    );

    $headers = $service->getTimeslotHeaders();

    // Expected: 1:30 PM, 2:00 PM, [2:30 PM], 3:00 PM, 3:30 PM
    // The requested time (2:30 PM) should be in the center (index 2)
    expect($headers[2])->toBe('2:30 PM', 'Requested time should be centered with offset 2')
        ->and($headers[0])->toBe('1:30 PM', 'First slot should be 1 hour before requested time')
        ->and($headers[4])->toBe('3:30 PM', 'Last slot should be 1 hour after requested time')
        ->and($headers)->toBe(['1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM']);
});

test('api default behavior with offset 1', function () {
    // This test documents the current API behavior with offset 1
    $region = Region::where('id', 'miami')->first();
    $requestTime = '14:30:00'; // 2:30 PM
    $date = '2025-06-21';
    $guestCount = 2;
    $timeslotCount = 5;

    $service = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 1, // This is what API currently uses as default
        region: $region
    );

    $headers = $service->getTimeslotHeaders();

    // Expected: 2:00 PM, [2:30 PM], 3:00 PM, 3:30 PM, 4:00 PM
    // The requested time (2:30 PM) should be in the second position (index 1)
    expect($headers[1])->toBe('2:30 PM', 'Requested time should be second slot with offset 1')
        ->and($headers[0])->toBe('2:00 PM', 'First slot should be 30 minutes before requested time')
        ->and($headers[4])->toBe('4:00 PM', 'Last slot should be 1.5 hours after requested time')
        ->and($headers)->toBe(['2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM']);
});

test('demonstrates the user reported issue - expecting 1:30 PM to 3:30 PM for 2:30 PM request', function () {
    // This test demonstrates the specific issue the user reported:
    // When requesting 2:30 PM, they expect to see 1:30 PM to 3:30 PM (offset 2 behavior)
    // But the API currently returns 2:00 PM to 4:00 PM (offset 1 behavior)

    $region = Region::where('id', 'miami')->first();
    $requestTime = '14:30:00'; // 2:30 PM
    $date = '2025-06-21';
    $guestCount = 2;
    $timeslotCount = 5;

    // What the API currently returns with default offset 1
    $currentApiService = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 1, // Current API default
        region: $region
    );

    $currentApiHeaders = $currentApiService->getTimeslotHeaders();
    expect($currentApiHeaders)->toBe(['2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM']);

    // What the user expects (and what VIP calendar provides with offset 2)
    $expectedService = new ReservationService(
        date: $date,
        guestCount: $guestCount,
        reservationTime: $requestTime,
        timeslotCount: $timeslotCount,
        timeSlotOffset: 2, // What user expects
        region: $region
    );

    $expectedHeaders = $expectedService->getTimeslotHeaders();
    expect($expectedHeaders)->toBe(['1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM']);

    // Demonstrate the difference
    expect($currentApiHeaders)->not->toBe($expectedHeaders, 'Current API behavior differs from user expectation');
    expect($expectedHeaders)->toContain('1:30 PM')
        ->and($expectedHeaders)->toContain('3:30 PM')
        ->and($expectedHeaders[2])->toBe('2:30 PM');
});
