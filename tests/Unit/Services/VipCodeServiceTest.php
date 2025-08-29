<?php

use App\Models\Concierge;
use App\Models\VipCode;
use App\Models\VipSession;
use App\Services\VipCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(VipCodeService::class);

    // Create test concierge and VIP codes
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

describe('findByCode', function () {
    test('finds VIP code by exact match', function () {
        $result = $this->service->findByCode('TESTCODE');

        expect($result)->not->toBeNull()
            ->and($result->code)->toBe('TESTCODE')
            ->and($result->concierge)->not->toBeNull();
    });

    test('finds VIP code case insensitively', function () {
        $result = $this->service->findByCode('testcode');

        expect($result)->not->toBeNull()
            ->and($result->code)->toBe('TESTCODE');
    });

    test('returns null for non-existent code', function () {
        $result = $this->service->findByCode('NONEXISTENT');

        expect($result)->toBeNull();
    });

    test('only finds active VIP codes', function () {
        VipCode::where('code', 'TESTCODE')->update(['is_active' => false]);

        $result = $this->service->findByCode('TESTCODE');

        expect($result)->toBeNull();
    });
});

describe('createVipSession', function () {
    test('creates session with valid VIP code', function () {
        $result = $this->service->createVipSession('TESTCODE');

        expect($result)->not->toBeNull()
            ->and($result['token'])->toBeString()
            ->and($result['expires_at'])->toBeString()
            ->and($result['vip_code']->code)->toBe('TESTCODE');

        // Check that VIP session was created in database
        expect(VipSession::where('vip_code_id', $this->vipCode->id)->exists())->toBeTrue();

        // Check that anonymous token was generated (not a Sanctum token)
        expect($result['token'])->toBeString()
            ->and(strlen($result['token']))->toBe(64); // SHA256 hash length
    });

    test('uses fallback code when invalid code provided', function () {
        $result = $this->service->createVipSession('INVALIDCODE');

        expect($result)->not->toBeNull()
            ->and($result['vip_code']->code)->toBe('ALEX');

        // Check that VIP session was created with fallback code
        expect(VipSession::where('vip_code_id', $this->fallbackVipCode->id)->exists())->toBeTrue();
    });

    test('returns null when both code and fallback are invalid', function () {
        // Make fallback code inactive
        VipCode::where('code', 'ALEX')->update(['is_active' => false]);

        $result = $this->service->createVipSession('INVALIDCODE');

        expect($result)->toBeNull();
    });

    test('returns null when fallback code is disabled in config', function () {
        config(['app.vip.fallback_code' => null]);

        $result = $this->service->createVipSession('INVALIDCODE');

        expect($result)->toBeNull();
    });

    test('uses configured session duration', function () {
        config(['app.vip.session_duration_hours' => 12]);

        $result = $this->service->createVipSession('TESTCODE');

        $expiresAt = now()->addHours(12);
        $resultExpiresAt = now()->parse($result['expires_at']);

        expect($resultExpiresAt->diffInMinutes($expiresAt))->toBeLessThan(1);
    });

    test('cleans up expired sessions for VIP code', function () {
        // Create expired session
        VipSession::create([
            'vip_code_id' => $this->vipCode->id,
            'token' => 'expired_token_hash',
            'expires_at' => now()->subHour(),
            'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
        ]);

        $this->service->createVipSession('TESTCODE');

        // Should have cleaned up expired session
        expect(VipSession::where('token', 'expired_token_hash')->exists())->toBeFalse();
    });
});

describe('validateSessionToken', function () {
    test('validates valid session token', function () {
        // Create session first
        $sessionData = $this->service->createVipSession('TESTCODE');

        $result = $this->service->validateSessionToken($sessionData['token']);

        expect($result)->not->toBeNull()
            ->and($result['session'])->not->toBeNull()
            ->and($result['vip_code']->code)->toBe('TESTCODE');
    });

    test('returns null for invalid token', function () {
        $result = $this->service->validateSessionToken('invalid_token');

        expect($result)->toBeNull();
    });

    test('returns null for expired token', function () {
        // Create expired session
        VipSession::create([
            'vip_code_id' => $this->vipCode->id,
            'token' => 'expired_token_hash',
            'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
            'expires_at' => now()->subHour(),
        ]);

        $result = $this->service->validateSessionToken('expired_token_hash');

        expect($result)->toBeNull();
    });

    test('returns null for non-VIP session token', function () {
        // Create a token that doesn't exist in our VIP sessions
        $nonExistentToken = 'non_existent_token_hash';

        $result = $this->service->validateSessionToken($nonExistentToken);

        expect($result)->toBeNull();
    });
});

describe('cleanupExpiredSessions', function () {
    test('cleans up expired sessions and tokens', function () {
        // Create expired sessions
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

        $cleanupCount = $this->service->cleanupExpiredSessions();

        expect($cleanupCount)->toBe(2);

        // Should have cleaned up expired sessions
        expect(VipSession::where('expires_at', '<', now())->count())->toBe(0);

        // Should have left active session
        expect(VipSession::where('expires_at', '>', now())->count())->toBe(1);
    });

    test('returns zero when no expired sessions exist', function () {
        $cleanupCount = $this->service->cleanupExpiredSessions();

        expect($cleanupCount)->toBe(0);
    });
});

describe('configuration', function () {
    test('uses custom fallback code from config', function () {
        config(['app.vip.fallback_code' => 'CUSTOM']);

        // Create custom fallback code
        $customConcierge = Concierge::factory()->create();
        VipCode::create([
            'code' => 'CUSTOM',
            'concierge_id' => $customConcierge->id,
            'is_active' => true,
        ]);

        $result = $this->service->createVipSession('INVALIDCODE');

        expect($result)->not->toBeNull()
            ->and($result['vip_code']->code)->toBe('CUSTOM');
    });

    test('uses custom session duration from config', function () {
        config(['app.vip.session_duration_hours' => 48]);

        $result = $this->service->createVipSession('TESTCODE');

        $expiresAt = now()->addHours(48);
        $resultExpiresAt = now()->parse($result['expires_at']);

        expect($resultExpiresAt->diffInMinutes($expiresAt))->toBeLessThan(1);
    });
});
