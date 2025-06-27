<?php

use App\Models\User;

use function Pest\Laravel\postJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user cannot update push token', function () {
    postJson('/api/update-push-token', [
        'token' => 'test-push-token',
        'device_id' => 'test-device-id',
    ])
        ->assertUnauthorized();
});

test('authenticated user can update push token', function () {
    postJson('/api/update-push-token', [
        'push_token' => 'test-push-token',
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful();
});

test('push token update requires token', function () {
    postJson('/api/update-push-token', [], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['push_token']);
});

test('push token update stores token in database', function () {
    // Assuming there's a push_tokens table or similar
    $testToken = 'test-push-token';
    postJson('/api/update-push-token', [
        'push_token' => $testToken,

    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful();

    $exists = DB::table('users')->where('expo_push_token', $testToken)->exists();
    expect($exists)->toBeTrue();
});
