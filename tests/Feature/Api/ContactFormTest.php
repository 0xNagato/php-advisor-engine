<?php

use App\Models\User;

use function Pest\Laravel\postJson;

beforeEach(function () {
    // Get our test concierge user that has multiple roles
    $this->user = User::role('concierge')->first();

    // Create a token for API authentication
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user cannot submit contact form', function () {
    postJson('/api/contact', [
        'subject' => 'Test Subject',
        'message' => 'Test message content',
    ])
        ->assertUnauthorized();
});

test('authenticated user can submit contact form', function () {
    postJson('/api/contact', [
        'subject' => 'Test Subject',
        'message' => 'Test message content',
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful();
});

test('contact form does not requires subject', function () {
    postJson('/api/contact', [
        'message' => 'Test message content',
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful();
});

test('contact form requires message', function () {
    postJson('/api/contact', [
        'subject' => 'Test Subject',
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('contact form message has maximum length', function () {
    postJson('/api/contact', [
        'subject' => 'Test Subject',
        'message' => str_repeat('a', 5001), // Assuming max length is 5000
    ], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});
