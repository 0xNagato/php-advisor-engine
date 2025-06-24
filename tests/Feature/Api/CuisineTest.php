<?php

use App\Models\Cuisine;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user can access cuisines', function () {
    $allCuisines = Cuisine::query()->pluck('name', 'id')->toArray();

    $response = getJson('/api/cuisines')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($allCuisines);
});

test('authenticated user can fetch all cuisines', function () {
    $allCuisines = Cuisine::query()->pluck('name', 'id')->toArray();

    $response = getJson('/api/cuisines', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($allCuisines);
});

test('cuisine response contains correct data structure', function () {
    $response = getJson('/api/cuisines', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');

    // Check that we have the expected number of cuisines
    $expectedCount = Cuisine::count();
    expect(count($responseData))->toBe($expectedCount);

    // Check that the response contains all cuisines from the database
    $dbCuisines = Cuisine::getNamesList();
    expect($responseData)->toBe($dbCuisines)
        ->and($responseData)->toHaveKey('italian')
        ->and($responseData)->toHaveKey('japanese')
        ->and($responseData)->toHaveKey('mexican')
        ->and($responseData['italian'])->toBe('Italian')
        ->and($responseData['japanese'])->toBe('Japanese')
        ->and($responseData['mexican'])->toBe('Mexican');

});
