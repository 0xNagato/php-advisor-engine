<?php

use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'test@example.com',
    ]);
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user cannot access me endpoint', function () {
    getJson('/api/me')
        ->assertUnauthorized();
});

test('authenticated user can access me endpoint', function () {
    getJson('/api/me', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'role',
                    'email',
                    'name',
                    'avatar',
                    'timezone',
                    'region',
                ],
            ],
        ]);
});

test('me endpoint returns correct user data', function () {
    getJson('/api/me', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJson([
            'data' => [
                'user' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ],
            ],
        ]);
});

test('me endpoint includes user role', function () {
    getJson('/api/me', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'role',
                ],
            ],
        ]);
});
