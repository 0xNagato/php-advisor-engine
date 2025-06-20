<?php

use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user cannot access timeslots', function () {
    getJson('/api/timeslots')
        ->assertUnauthorized();
});

test('authenticated user can fetch timeslots', function () {
    $date = now()->format('Y-m-d');

    getJson("/api/timeslots?date={$date}", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('timeslots can be filtered by date', function () {
    $date = now()->format('Y-m-d');

    getJson("/api/timeslots?date={$date}", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('authenticated user can fetch timeslots with region parameter', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Test with a specific region
    getJson("/api/timeslots?date={$date}&region=miami", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    // Test with a different region
    getJson("/api/timeslots?date={$date}&region=paris", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('timeslots endpoint rejects invalid region parameter', function () {
    $date = now()->addDay()->format('Y-m-d');

    getJson("/api/timeslots?date={$date}&region=invalid_region", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422)
        ->assertJson([
            'region' => [
                'The selected region is invalid.',
            ],
        ]);
});
