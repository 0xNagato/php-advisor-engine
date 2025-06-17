<?php

use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Specialty;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user cannot access availability calendar', function () {
    getJson('/api/calendar')
        ->assertUnauthorized();
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

test('venues are filtered correctly by cuisine', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Create venues with specific cuisines
    $italianCuisine = Cuisine::where('name', 'Italian')->first()->id;
    $japaneseCuisine = Cuisine::where('name', 'Japanese')->first()->id;

    // Create 2 venues with Italian cuisine
    $italianVenue1 = \App\Models\Venue::factory()->create([
        'cuisines' => [$italianCuisine],
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    $italianVenue2 = \App\Models\Venue::factory()->create([
        'cuisines' => [$italianCuisine],
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with Japanese cuisine
    $japaneseVenue = \App\Models\Venue::factory()->create([
        'cuisines' => [$japaneseCuisine],
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with both cuisines
    $bothCuisinesVenue = \App\Models\Venue::factory()->create([
        'cuisines' => [$italianCuisine, $japaneseCuisine],
        'status' => \App\Enums\VenueStatus::ACTIVE,
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
    $brickellVenue1 = \App\Models\Venue::factory()->create([
        'neighborhood' => $brickellNeighborhood,
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    $brickellVenue2 = \App\Models\Venue::factory()->create([
        'neighborhood' => $brickellNeighborhood,
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue in South Beach
    $southBeachVenue = \App\Models\Venue::factory()->create([
        'neighborhood' => $southBeachNeighborhood,
        'status' => \App\Enums\VenueStatus::ACTIVE,
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
    $waterfrontVenue1 = \App\Models\Venue::factory()->create([
        'specialty' => [$waterfrontSpecialty],
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    $waterfrontVenue2 = \App\Models\Venue::factory()->create([
        'specialty' => [$waterfrontSpecialty],
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with Fine Dining specialty
    $fineDiningVenue = \App\Models\Venue::factory()->create([
        'specialty' => [$finingDiningSpecialty],
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'region' => 'miami',
    ]);

    // Create 1 venue with both specialties
    $bothSpecialtiesVenue = \App\Models\Venue::factory()->create([
        'specialty' => [$waterfrontSpecialty, $finingDiningSpecialty],
        'status' => \App\Enums\VenueStatus::ACTIVE,
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
