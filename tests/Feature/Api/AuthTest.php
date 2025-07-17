<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\postJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'email' => 'testuser@example.com',
        'password' => Hash::make('password123'),
    ]);
});

test('login with valid credentials', function () {
    $response = postJson('/api/login', [
        'email' => $this->user->email,
        'password' => 'password123',
        'device_name' => 'Test Device',
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
            ],
            'token',
        ]);

    // Verify the response contains the correct user data
    $responseData = $response->json();
    expect($responseData['user']['email'])->toBe($this->user->email);
    expect($responseData)->toHaveKey('token');
});

test('login with invalid credentials', function () {
    postJson('/api/login', [
        'email' => $this->user->email,
        'password' => 'incorrect-password',
        'device_name' => 'Test Device',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login with missing fields', function () {
    postJson('/api/login', [
        'email' => $this->user->email,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password', 'device_name']);
});

test('unauthenticated user cannot access logout endpoint', function () {
    postJson('/api/logout')
        ->assertUnauthorized();
});

test('authenticated user can log out successfully', function () {
    $token = $this->user->createToken('Test Token')->plainTextToken;

    postJson('/api/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ])
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Successfully logged out.',
        ]);
});
