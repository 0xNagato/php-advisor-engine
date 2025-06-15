<?php

use App\Models\Neighborhood;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user cannot access neighborhoods', function () {
    getJson('/api/neighborhoods')
        ->assertUnauthorized();
});

test('authenticated user can fetch all neighborhoods', function () {
    getJson('/api/neighborhoods', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('authenticated user can filter neighborhoods by region', function () {
    $region = 'miami';
    $miamiNeighborhoods = Neighborhood::query()->where('region', $region)->pluck('name', 'id')->toArray();

    $response = getJson('/api/neighborhoods?region='.$region, [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($miamiNeighborhoods);

    // Ensure only Miami neighborhoods are returned
    foreach ($responseData as $id => $name) {
        $neighborhood = Neighborhood::find($id);
        expect($neighborhood->region)->toBe($region);
    }
});
