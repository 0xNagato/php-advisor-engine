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
