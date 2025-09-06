<?php

use App\Models\Region;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;

    // Get the first region (usually NYC in tests)
    $this->region = Region::first();

    // If no region exists, skip the test
    if (! $this->region) {
        $this->markTestSkipped('No regions found in database');
    }
});

it('calculates approximate drive times when user coordinates are provided', function () {
    // Create venues with different coordinates
    $venue1 = Venue::factory()->create([
        'name' => 'Near Venue',
        'region' => $this->region->id,
        'latitude' => 40.7580, // ~5 miles from user
        'longitude' => -73.9855,
        'status' => 'active',
    ]);

    $venue2 = Venue::factory()->create([
        'name' => 'Far Venue',
        'region' => $this->region->id,
        'latitude' => 40.9176, // ~20 miles from user
        'longitude' => -73.7004,
        'status' => 'active',
    ]);

    $venue3 = Venue::factory()->create([
        'name' => 'No Coords Venue',
        'region' => $this->region->id,
        'latitude' => null,
        'longitude' => null,
        'status' => 'active',
    ]);

    // Create schedule templates for venues
    foreach ([$venue1, $venue2, $venue3] as $venue) {
        ScheduleTemplate::factory()->create([
            'venue_id' => $venue->id,
            'day_of_week' => strtolower(now()->format('l')),
            'start_time' => '19:00:00',
            'is_available' => true,
            'available_tables' => 5,
            'party_size' => 2,
        ]);
    }

    // Make request with user coordinates (Times Square, NYC)
    $response = getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00',
        'user_latitude' => 40.7580,
        'user_longitude' => -73.9855,
        'region' => $this->region->id,
    ]), [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk();

    $venues = $response->json('data.venues');

    // Find venues in response
    $nearVenueData = collect($venues)->firstWhere('id', $venue1->id);
    $farVenueData = collect($venues)->firstWhere('id', $venue2->id);
    $noCoordVenueData = collect($venues)->firstWhere('id', $venue3->id);

    // Near venue should have shorter drive time and distance
    expect($nearVenueData)->not->toBeNull()
        ->and($nearVenueData)->toHaveKey('approx_minutes')
        ->and($nearVenueData['approx_minutes'])->toBeLessThan(10)
        ->and($nearVenueData)->toHaveKey('distance_miles')
        ->and($nearVenueData['distance_miles'])->toBeLessThan(5.0)
        ->and($nearVenueData)->toHaveKey('distance_km')
        ->and($nearVenueData['distance_km'])->toBeLessThan(8.0);

    // Far venue should have longer drive time and greater distance
    expect($farVenueData)->not->toBeNull()
        ->and($farVenueData)->toHaveKey('approx_minutes')
        ->and($farVenueData['approx_minutes'])->toBeGreaterThan(20)
        ->and($farVenueData)->toHaveKey('distance_miles')
        ->and($farVenueData['distance_miles'])->toBeGreaterThan(10.0)
        ->and($farVenueData)->toHaveKey('distance_km')
        ->and($farVenueData['distance_km'])->toBeGreaterThan(16.0);

    // Venue without coordinates should not have distance/time fields
    expect($noCoordVenueData)->not->toBeNull()
        ->and($noCoordVenueData)->not->toHaveKey('approx_minutes')
        ->and($noCoordVenueData)->not->toHaveKey('distance_miles')
        ->and($noCoordVenueData)->not->toHaveKey('distance_km');
});

it('does not include approx_minutes when user coordinates are not provided', function () {
    $venue = Venue::factory()->create([
        'name' => 'Test Venue',
        'region' => $this->region->id,
        'latitude' => 40.7580,
        'longitude' => -73.9855,
        'status' => 'active',
    ]);

    ScheduleTemplate::factory()->create([
        'venue_id' => $venue->id,
        'day_of_week' => strtolower(now()->format('l')),
        'start_time' => '19:00:00',
        'is_available' => true,
        'available_tables' => 5,
        'party_size' => 2,
    ]);

    // Make request without user coordinates
    $response = getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00',
        'region' => $this->region->id,
    ]), [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk();

    $venues = $response->json('data.venues');
    $venueData = collect($venues)->firstWhere('id', $venue->id);

    expect($venueData)->not->toBeNull()
        ->and($venueData)->not->toHaveKey('approx_minutes')
        ->and($venueData)->not->toHaveKey('distance_miles')
        ->and($venueData)->not->toHaveKey('distance_km');
});

it('validates latitude and longitude parameters', function () {
    // Invalid latitude (out of range)
    $response = getJson('/api/calendar?'.http_build_query([
        'date' => now()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00',
        'user_latitude' => 91, // Invalid: > 90
        'user_longitude' => -73.9855,
    ]), [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertStatus(422);
    expect($response->json())->toHaveKey('user_latitude');

    // Invalid longitude (out of range)
    $response = getJson('/api/calendar?'.http_build_query([
        'date' => now()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00',
        'user_latitude' => 40.7580,
        'user_longitude' => 181, // Invalid: > 180
    ]), [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertStatus(422);
    expect($response->json())->toHaveKey('user_longitude');
});
