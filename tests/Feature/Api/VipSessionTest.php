<?php

use App\Models\Concierge;
use App\Models\VipCode;
use App\Models\VipSession;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Create a test concierge and VIP code
    $this->concierge = Concierge::factory()->create();
    $this->vipCode = VipCode::create([
        'code' => 'TESTCODE',
        'concierge_id' => $this->concierge->id,
        'is_active' => true,
    ]);
});

test('can create VIP session with valid code', function () {
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'TESTCODE',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'session_token',
                'expires_at',
                'is_demo',
                'vip_code' => [
                    'id',
                    'code',
                    'concierge' => [
                        'id',
                        'name',
                        'hotel_name',
                    ],
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'is_demo' => false,
                'vip_code' => [
                    'code' => 'TESTCODE',
                ],
            ],
        ]);

    // Check that a session was created in the database
    $this->assertDatabaseHas('vip_sessions', [
        'vip_code_id' => $this->vipCode->id,
    ]);
});

test('creates demo session for invalid VIP code', function () {
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'INVALIDCODE',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'session_token',
                'expires_at',
                'is_demo',
                'demo_message',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'is_demo' => true,
            ],
        ]);

    // Check that no session was created in the database for invalid code
    $this->assertDatabaseMissing('vip_sessions', [
        'vip_code_id' => $this->vipCode->id,
    ]);
});

test('validates VIP session token correctly', function () {
    // First create a session
    $createResponse = postJson('/api/vip/sessions', [
        'vip_code' => 'TESTCODE',
    ]);

    $sessionToken = $createResponse->json('data.session_token');

    // Now validate the session
    $validateResponse = postJson('/api/vip/sessions/validate', [
        'session_token' => $sessionToken,
    ]);

    $validateResponse->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'valid',
                'is_demo',
                'session' => [
                    'id',
                    'expires_at',
                ],
                'vip_code' => [
                    'id',
                    'code',
                    'concierge',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'valid' => true,
                'is_demo' => false,
            ],
        ]);
});

test('rejects invalid session token', function () {
    $response = postJson('/api/vip/sessions/validate', [
        'session_token' => 'invalid_token',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'data' => [
                'valid' => false,
                'message' => 'Invalid or expired session token',
            ],
        ]);
});

test('rejects expired session token', function () {
    // Create a session manually that's already expired
    $token = 'expired_test_token';
    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', $token),
        'expires_at' => now()->subHour(), // Already expired
    ]);

    $response = postJson('/api/vip/sessions/validate', [
        'session_token' => $token,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'data' => [
                'valid' => false,
                'message' => 'Invalid or expired session token',
            ],
        ]);
});

test('requires VIP code parameter for session creation', function () {
    $response = postJson('/api/vip/sessions', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['vip_code']);
});

test('requires session token parameter for validation', function () {
    $response = postJson('/api/vip/sessions/validate', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['session_token']);
});

test('VIP code is case insensitive', function () {
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'testcode', // lowercase
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'is_demo' => false,
                'vip_code' => [
                    'code' => 'TESTCODE', // Original uppercase code
                ],
            ],
        ]);
});

test('session analytics requires authentication', function () {
    $response = getJson('/api/vip/sessions/analytics');

    $response->assertStatus(401);
});

test('cleans up expired sessions correctly', function () {
    // Create some sessions, some expired
    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', 'expired1'),
        'expires_at' => now()->subHour(),
    ]);

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', 'active1'),
        'expires_at' => now()->addHour(),
    ]);

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', 'expired2'),
        'expires_at' => now()->subMinute(),
    ]);

    // Clean up expired sessions
    $this->vipCode->cleanExpiredSessions();

    // Should have only the active session left
    $this->assertEquals(1, VipSession::where('vip_code_id', $this->vipCode->id)->count());
    $this->assertEquals(
        hash('sha256', 'active1'),
        VipSession::where('vip_code_id', $this->vipCode->id)->first()->token
    );
});
