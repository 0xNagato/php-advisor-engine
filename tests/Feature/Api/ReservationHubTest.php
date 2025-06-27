<?php

use App\Enums\VenueStatus;
use App\Models\User;
use App\Models\Venue;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;

    // Create a test venue
    $this->venue = Venue::factory()->create([
        'status' => VenueStatus::ACTIVE,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);
});

test('unauthenticated user cannot access reservation hub', function () {
    getJson('/api/hub')
        ->assertUnauthorized();
});

test('authenticated user can fetch reservation hub', function () {
    $date = now()->addDay()->format('Y-m-d');
    getJson("/api/hub?date=$date&guest_count=2&reservation_time=14:30:00&venue_id={$this->venue->id}", [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});
