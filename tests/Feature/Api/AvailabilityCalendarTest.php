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
