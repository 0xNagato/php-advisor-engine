<?php

use App\Models\Concierge;
use App\Models\User;
use App\Models\VipCode;
use App\Models\VipSession;
use Laravel\Sanctum\PersonalAccessToken;

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

    // Check that a Sanctum token was created
    $sessionToken = $response->json('data.session_token');
    $this->assertNotNull(PersonalAccessToken::findToken($sessionToken));
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
                    'code' => 'ALEX',
                ],
            ],
        ]);

    // Check that a VIP session was created with fallback code
    $this->assertDatabaseHas('vip_sessions', [
        'vip_code_id' => $this->fallbackVipCode->id,
    ]);

    // Check that a Sanctum token was created for fallback concierge
    $sessionToken = $response->json('data.session_token');
    $sanctumToken = PersonalAccessToken::findToken($sessionToken);
    $this->assertNotNull($sanctumToken);
    $this->assertEquals($this->fallbackConcierge->user->id, $sanctumToken->tokenable_id);
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
    // Create a Sanctum token that's already expired
    $expiredToken = $this->concierge->user->createToken('expired-test', ['*'], now()->subHour());

    // Create corresponding VIP session record
    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', $expiredToken->plainTextToken),
        'sanctum_token_id' => $expiredToken->accessToken->id,
        'expires_at' => now()->subHour(),
    ]);

    $response = postJson('/api/vip/sessions/validate', [
        'session_token' => $expiredToken->plainTextToken,
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
    // Create expired Sanctum tokens and VIP sessions
    $expiredToken1 = $this->concierge->user->createToken('expired1', ['*'], now()->subHour());
    $expiredToken2 = $this->concierge->user->createToken('expired2', ['*'], now()->subMinute());
    $activeToken = $this->concierge->user->createToken('active', ['*'], now()->addHour());

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', $expiredToken1->plainTextToken),
        'sanctum_token_id' => $expiredToken1->accessToken->id,
        'expires_at' => now()->subHour(),
    ]);

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', $expiredToken2->plainTextToken),
        'sanctum_token_id' => $expiredToken2->accessToken->id,
        'expires_at' => now()->subMinute(),
    ]);

    VipSession::create([
        'vip_code_id' => $this->vipCode->id,
        'token' => hash('sha256', $activeToken->plainTextToken),
        'sanctum_token_id' => $activeToken->accessToken->id,
        'expires_at' => now()->addHour(),
    ]);

    // Clean up expired sessions using the service
    $cleanupCount = app(\App\Services\VipCodeService::class)->cleanupExpiredSessions();

    // Should have cleaned up 2 expired sessions
    $this->assertEquals(2, $cleanupCount);

    // Should have only the active session left
    $this->assertEquals(1, VipSession::where('vip_code_id', $this->vipCode->id)->count());

    // Should have cleaned up the expired Sanctum tokens too
    $this->assertNull(PersonalAccessToken::find($expiredToken1->accessToken->id));
    $this->assertNull(PersonalAccessToken::find($expiredToken2->accessToken->id));
    $this->assertNotNull(PersonalAccessToken::find($activeToken->accessToken->id));
});

test('VIP session token works with API authentication', function () {
    // Create a VIP session
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'TESTCODE',
    ]);

    $sessionToken = $response->json('data.session_token');

    // Test that the token works with authenticated endpoints
    $meResponse = getJson('/api/me', [
        'Authorization' => 'Bearer '.$sessionToken,
    ]);

    $meResponse->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ]);

    // Should return the concierge user, not the demo user
    $this->assertEquals($this->concierge->user->id, $meResponse->json('data.user.id'));
});

test('fallback session token works with API authentication', function () {
    // Create a fallback session with invalid code
    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'INVALIDCODE',
    ]);

    $sessionToken = $response->json('data.session_token');

    // Test that the fallback token works with authenticated endpoints
    $meResponse = getJson('/api/me', [
        'Authorization' => 'Bearer '.$sessionToken,
    ]);

    $meResponse->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ]);

    // Should return the fallback concierge user
    $this->assertEquals($this->fallbackConcierge->user->id, $meResponse->json('data.user.id'));
});
