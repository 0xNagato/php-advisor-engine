<?php

use App\Enums\VenueStatus;
use App\Models\Concierge;
use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Venue;
use App\Models\VipCode;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;

    // Create VIP code for VIP session authentication
    $this->concierge = Concierge::factory()->create();
    $this->vipCode = VipCode::create([
        'code' => 'TESTCODE',
        'concierge_id' => $this->concierge->id,
        'is_active' => true,
    ]);

    // Create VIP session
    $vipSessionResponse = $this->postJson('/api/vip/sessions', [
        'vip_code' => 'TESTCODE',
    ]);
    $this->vipSessionToken = $vipSessionResponse->json('data.session_token');
});

test('unauthenticated user cannot access availability calendar', function () {
    getJson('/api/calendar')
        ->assertStatus(401); // Authentication required
});

test('VIP session user can access availability calendar (returns validation error)', function () {
    getJson('/api/calendar', [
        'Authorization' => 'Bearer '.$this->vipSessionToken,
    ])
        ->assertStatus(422); // Validation error for missing required parameters
});

test('authenticated user can fetch availability calendar', function () {
    $date = now()->addDay()->format('Y-m-d');
    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);
});

test('authenticated user can fetch availability calendar with cuisine filter', function () {
    $date = now()->addDay()->format('Y-m-d');
    $cuisine = Cuisine::first()->id;

    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&cuisine[]=$cuisine", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);
});

test('authenticated user can fetch availability calendar with neighborhood filter', function () {
    $date = now()->addDay()->format('Y-m-d');
    $neighborhood = Neighborhood::where('region', 'miami')->first()->id;

    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&neighborhood=$neighborhood", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);
});

test('authenticated user can fetch availability calendar with specialty filter', function () {
    $date = now()->addDay()->format('Y-m-d');
    $specialty = Specialty::first()->id;

    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&specialty[]=$specialty", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);
});

test('authenticated user can fetch availability calendar with all filters', function () {
    $date = now()->addDay()->format('Y-m-d');
    $cuisine = Cuisine::first()->id;
    $neighborhood = Neighborhood::where('region', 'miami')->first()->id;
    $specialty = Specialty::first()->id;

    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&cuisine[]=$cuisine&neighborhood=$neighborhood&specialty[]=$specialty", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);
});

test('authenticated user can fetch availability calendar with region parameter', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Test with a specific region
    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&region=miami", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);

    // Test with a different region
    getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&region=paris", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'venues',
                'timeslots',
            ],
        ]);
});

test('availability calendar rejects invalid region parameter', function () {
    $date = now()->addDay()->format('Y-m-d');

    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&region=invalid_region", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'region' => [
                'The selected region is invalid.',
            ],
        ]);
});

test('venues are filtered correctly by cuisine', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Create venues with specific cuisines
    $italianCuisine = Cuisine::where('name', 'Italian')->first()->id;
    $japaneseCuisine = Cuisine::where('name', 'Japanese')->first()->id;

    // Create 2 venues with Italian cuisine
    $italianVenue1 = Venue::factory()->create([
        'cuisines' => [$italianCuisine],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    $italianVenue2 = Venue::factory()->create([
        'cuisines' => [$italianCuisine],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with Japanese cuisine
    $japaneseVenue = Venue::factory()->create([
        'cuisines' => [$japaneseCuisine],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with both cuisines
    $bothCuisinesVenue = Venue::factory()->create([
        'cuisines' => [$italianCuisine, $japaneseCuisine],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create schedules for each venue to ensure they appear in the results
    $venues = [$italianVenue1, $italianVenue2, $japaneseVenue, $bothCuisinesVenue];
    foreach ($venues as $venue) {
        // Create a schedule for the venue
        $venue->createDefaultSchedules();
    }

    // Test filtering by Italian cuisine
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&cuisine[]=$italianCuisine", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 3 venues (2 Italian + 1 with both cuisines)
    $this->assertCount(3, $venues);

    // Test filtering by Japanese cuisine
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&cuisine[]=$japaneseCuisine", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 2 venues (1 Japanese + 1 with both cuisines)
    $this->assertCount(2, $venues);

    // Test filtering by both cuisines
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&cuisine[]=$italianCuisine&cuisine[]=$japaneseCuisine", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 4 venues (all venues with either cuisine)
    $this->assertCount(4, $venues);
});

test('venues are filtered correctly by neighborhood', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Get neighborhoods
    $brickellNeighborhood = Neighborhood::where('name', 'Brickell')->first()->id;
    $southBeachNeighborhood = Neighborhood::where('name', 'South Beach')->first()->id;

    // Create 2 venues in Brickell
    $brickellVenue1 = Venue::factory()->create([
        'neighborhood' => $brickellNeighborhood,
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    $brickellVenue2 = Venue::factory()->create([
        'neighborhood' => $brickellNeighborhood,
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue in South Beach
    $southBeachVenue = Venue::factory()->create([
        'neighborhood' => $southBeachNeighborhood,
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create schedules for each venue to ensure they appear in the results
    $venues = [$brickellVenue1, $brickellVenue2, $southBeachVenue];
    foreach ($venues as $venue) {
        // Create a schedule for the venue
        $venue->createDefaultSchedules();
    }

    // Test filtering by Brickell
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&neighborhood=$brickellNeighborhood", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 2 venues in Brickell
    $this->assertCount(2, $venues);

    // Test filtering by South Beach
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&neighborhood=$southBeachNeighborhood", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 1 venue in South Beach
    $this->assertCount(1, $venues);
});

test('venues are filtered correctly by specialty', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Get specialties
    $waterfrontSpecialty = Specialty::where('name', 'Waterfront')->first()->id;
    $finingDiningSpecialty = Specialty::where('name', 'Fine Dining')->first()->id;

    // Create 2 venues with Waterfront specialty
    $waterfrontVenue1 = Venue::factory()->create([
        'specialty' => [$waterfrontSpecialty],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    $waterfrontVenue2 = Venue::factory()->create([
        'specialty' => [$waterfrontSpecialty],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with Fine Dining specialty
    $fineDiningVenue = Venue::factory()->create([
        'specialty' => [$finingDiningSpecialty],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with both specialties
    $bothSpecialtiesVenue = Venue::factory()->create([
        'specialty' => [$waterfrontSpecialty, $finingDiningSpecialty],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create schedules for each venue to ensure they appear in the results
    $venues = [$waterfrontVenue1, $waterfrontVenue2, $fineDiningVenue, $bothSpecialtiesVenue];
    foreach ($venues as $venue) {
        // Create a schedule for the venue
        $venue->createDefaultSchedules();
    }

    // Test filtering by Waterfront
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&specialty[]=$waterfrontSpecialty", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 3 venues (2 Waterfront + 1 with both specialties)
    $this->assertCount(3, $venues);

    // Test filtering by Fine Dining
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&specialty[]=$finingDiningSpecialty", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 2 venues (1 Fine Dining + 1 with both specialties)
    $this->assertCount(2, $venues);

    // Test filtering by both specialties
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&specialty[]=$waterfrontSpecialty&specialty[]=$finingDiningSpecialty", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return 4 venues (all venues with either specialty)
    $this->assertCount(4, $venues);
});

test('authenticated user can fetch availability calendar with venue_id filter', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Create a specific venue
    $venue = Venue::factory()->create([
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create another venue to ensure filtering works
    $otherVenue = Venue::factory()->create([
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create schedules for both venues
    $venue->createDefaultSchedules();
    $otherVenue->createDefaultSchedules();

    // Test filtering by specific venue_id
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&venue_id={$venue->id}", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return only the specified venue
    $this->assertCount(1, $venues);
    $this->assertEquals($venue->id, $venues[0]['id']);
});

test('venue_id filter returns empty results when venue does not exist', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Use a non-existent venue ID
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&venue_id=99999", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'venue_id' => [
                'The selected venue id is invalid.',
            ],
        ]);
});

test('venue_id filter works with other filters', function () {
    $date = now()->addDay()->format('Y-m-d');
    $cuisine = Cuisine::first()->id;

    // Create a venue with specific cuisine
    $venue = Venue::factory()->create([
        'cuisines' => [$cuisine],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create another venue with different cuisine
    $otherVenue = Venue::factory()->create([
        'cuisines' => [Cuisine::where('id', '!=', $cuisine)->first()->id],
        'status' => VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create schedules for both venues
    $venue->createDefaultSchedules();
    $otherVenue->createDefaultSchedules();

    // Test filtering by venue_id and cuisine (should still return the specific venue)
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&venue_id={$venue->id}&cuisine[]=$cuisine", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return only the specified venue regardless of other filters
    $this->assertCount(1, $venues);
    $this->assertEquals($venue->id, $venues[0]['id']);
});

test('venue_id filter respects region boundaries', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Create a venue in a different region
    $venueInParis = Venue::factory()->create([
        'status' => VenueStatus::ACTIVE,
        'region' => 'paris',
    ]);

    // Create schedules for the venue
    $venueInParis->createDefaultSchedules();

    // Test filtering by venue_id with miami region (default for test user)
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&venue_id={$venueInParis->id}", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return empty because venue is in different region
    $this->assertCount(0, $venues);

    // Now test with correct region
    $response = getJson("/api/calendar?date=$date&guest_count=2&reservation_time=14:30:00&venue_id={$venueInParis->id}&region=paris", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertSuccessful();
    $venues = $response->json('data.venues');

    // Should return the venue when region matches
    $this->assertCount(1, $venues);
    $this->assertEquals($venueInParis->id, $venues[0]['id']);
});
