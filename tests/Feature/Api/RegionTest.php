<?php

use App\Models\Region;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('can fetch regions', function () {
    getJson('/api/regions')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('user can update their region', function () {
    $region = Region::first();

    postJson('/api/regions', [
        'region' => $region->id,
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertNoContent();

    expect($this->user->fresh()->region)->toBe($region->id);
});

test('user cannot set invalid region', function () {
    postJson('/api/regions', [
        'region' => 999, // Non-existent region
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422);
});
