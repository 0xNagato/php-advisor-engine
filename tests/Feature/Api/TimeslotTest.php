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
