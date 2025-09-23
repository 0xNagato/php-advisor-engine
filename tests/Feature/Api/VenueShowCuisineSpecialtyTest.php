<?php

use App\Models\User;
use App\Models\Venue;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('venue show endpoint returns cuisines and specialties as key-value pairs', function () {
    $venue = Venue::factory()->create([
        'cuisines' => ['japanese', 'chinese'],
        'specialty' => ['live_music_dj', 'rooftop'],
    ]);

    $response = $this->getJson("/api/venues/{$venue->id}", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'cuisines' => [
                    '*' => ['id', 'name'],
                ],
                'specialty' => [
                    '*' => ['id', 'name'],
                ],
            ],
        ]);

    // Check the actual values
    $data = $response->json('data');

    // Check cuisines
    expect($data['cuisines'])->toHaveCount(2);
    expect($data['cuisines'][0])->toMatchArray([
        'id' => 'japanese',
        'name' => 'Japanese',
    ]);
    expect($data['cuisines'][1])->toMatchArray([
        'id' => 'chinese',
        'name' => 'Chinese',
    ]);

    // Check specialties
    expect($data['specialty'])->toHaveCount(2);
    expect($data['specialty'][0])->toMatchArray([
        'id' => 'live_music_dj',
        'name' => 'Live Music/DJ',
    ]);
    expect($data['specialty'][1])->toMatchArray([
        'id' => 'rooftop',
        'name' => 'Rooftop',
    ]);
});
