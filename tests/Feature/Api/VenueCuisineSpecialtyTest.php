<?php

use App\Models\Region;
use App\Models\User;
use App\Models\Venue;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('availability calendar returns cuisines and specialties as key-value pairs', function () {
    $region = Region::first();
    $venue = Venue::factory()->create([
        'region' => $region->id,
        'status' => 'active',
        'cuisines' => ['mediterranean', 'italian'],
        'specialty' => ['family_friendly', 'fine_dining', 'rooftop'],
    ]);

    $response = $this->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00',
        'region' => $region->id,
    ]), [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'venues' => [
                    '*' => [
                        'cuisines' => [
                            '*' => ['id', 'name'],
                        ],
                        'specialty' => [
                            '*' => ['id', 'name'],
                        ],
                    ],
                ],
            ],
        ]);

    // Check the actual values
    $venueData = $response->json('data.venues.0');

    // Check cuisines
    expect($venueData['cuisines'])->toHaveCount(2);
    expect($venueData['cuisines'][0])->toMatchArray([
        'id' => 'mediterranean',
        'name' => 'Mediterranean',
    ]);
    expect($venueData['cuisines'][1])->toMatchArray([
        'id' => 'italian',
        'name' => 'Italian',
    ]);

    // Check specialties
    expect($venueData['specialty'])->toHaveCount(3);
    expect($venueData['specialty'][0])->toMatchArray([
        'id' => 'family_friendly',
        'name' => 'Family Friendly',
    ]);
    expect($venueData['specialty'][1])->toMatchArray([
        'id' => 'fine_dining',
        'name' => 'Fine Dining',
    ]);
    expect($venueData['specialty'][2])->toMatchArray([
        'id' => 'rooftop',
        'name' => 'Rooftop',
    ]);
});
