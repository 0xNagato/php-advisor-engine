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

    // Create fallback VIP code
    $this->fallbackConcierge = Concierge::factory()->create();
    $this->fallbackVipCode = VipCode::create([
        'code' => 'ALEX',
        'concierge_id' => $this->fallbackConcierge->id,
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
                'template',
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
                'template' => 'availability_calendar',
                'vip_code' => [
                    'code' => 'TESTCODE',
                ],
            ],
        ]);

    // Check that a session was created in the database
    $this->assertDatabaseHas('vip_sessions', [
        'vip_code_id' => $this->vipCode->id,
    ]);

    // Check that an anonymous token was generated (not a Sanctum token)
    $sessionToken = $response->json('data.session_token');
    $this->assertNotNull($sessionToken);
    $this->assertEquals(64, strlen($sessionToken)); // SHA256 hash length
});

test('uses fallback code for invalid VIP code', function () {
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'INVALIDCODE',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'session_token',
                'expires_at',
                'template',
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
                'template' => 'availability_calendar',
                'vip_code' => [
                    'code' => 'ALEX',
                ],
            ],
        ]);

    // Check that a VIP session was created with fallback code
    $this->assertDatabaseHas('vip_sessions', [
        'vip_code_id' => $this->fallbackVipCode->id,
    ]);

    // Check that an anonymous token was generated (not a Sanctum token)
    $sessionToken = $response->json('data.session_token');
    $this->assertNotNull($sessionToken);
    $this->assertEquals(64, strlen($sessionToken)); // SHA256 hash length
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
                'template',
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
                'template' => 'availability_calendar',
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
    // Create an expired VIP session
    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => 'expired_token_hash',
        'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
        'expires_at' => now()->subHour(),
    ]);

    $response = postJson('/api/vip/sessions/validate', [
        'session_token' => 'expired_token_hash',
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
                'template' => 'availability_calendar',
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
    // Create expired VIP sessions
    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => 'expired_token_1',
        'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
        'expires_at' => now()->subHour(),
    ]);

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => 'expired_token_2',
        'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
        'expires_at' => now()->subMinute(),
    ]);

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => 'active_token',
        'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
        'expires_at' => now()->addHour(),
    ]);

    // Clean up expired sessions using the service
    $cleanupCount = app(\App\Services\VipCodeService::class)->cleanupExpiredSessions();

    // Should have cleaned up 2 expired sessions
    $this->assertEquals(2, $cleanupCount);

    // Should have only the active session left
    $this->assertEquals(1, VipSession::where('vip_code_id', $this->vipCode->id)->count());
});

test('VIP session token does not work with standard API authentication', function () {
    // Create a VIP session
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'TESTCODE',
    ]);

    $sessionToken = $response->json('data.session_token');

    // Test that the anonymous token does NOT work with authenticated endpoints
    $meResponse = getJson('/api/me', [
        'Authorization' => 'Bearer '.$sessionToken,
    ]);

    // Should fail because anonymous tokens are not valid for standard API authentication
    $meResponse->assertStatus(401);
});

test('fallback session token does not work with standard API authentication', function () {
    // Create a fallback session with invalid code
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'INVALIDCODE',
    ]);

    $sessionToken = $response->json('data.session_token');

    // Test that the fallback anonymous token does NOT work with authenticated endpoints
    $meResponse = getJson('/api/me', [
        'Authorization' => 'Bearer '.$sessionToken,
    ]);

    // Should fail because anonymous tokens are not valid for standard API authentication
    $meResponse->assertStatus(401);
});
