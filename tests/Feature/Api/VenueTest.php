<?php

use App\Models\User;
use App\Models\Venue;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;

    // Create test venues
    Venue::factory()->count(3)->create();
});

test('unauthenticated user cannot access venues', function () {
    getJson('/api/venues')
        ->assertUnauthorized();
});

test('authenticated user can fetch venues', function () {
    getJson('/api/venues', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('venues can be filtered by region', function () {
    getJson('/api/venues?region_id=1', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('venues response includes required fields', function () {
    getJson('/api/venues', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'address',
                    'region_id',
                ],
            ],
        ]);
});
