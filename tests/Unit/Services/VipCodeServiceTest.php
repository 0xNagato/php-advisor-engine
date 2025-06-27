<?php

use App\Models\Concierge;
use App\Models\VipCode;
use App\Models\VipSession;
use App\Services\VipCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

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
            ->and($result['vip_code']->code)->toBe('TESTCODE')
            ->and($result['is_demo'])->toBeFalse();

        // Check that VIP session was created in database
        expect(VipSession::where('vip_code_id', $this->vipCode->id)->exists())->toBeTrue();

        // Check that Sanctum token exists
        expect(PersonalAccessToken::findToken($result['token']))->not->toBeNull();
    });

    test('uses fallback code when invalid code provided', function () {
        $result = $this->service->createVipSession('INVALIDCODE');

        expect($result)->not->toBeNull()
            ->and($result['vip_code']->code)->toBe('ALEX')
            ->and($result['is_demo'])->toBeFalse();

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
        // Create expired token first
        $expiredToken = $this->concierge->user->createToken('expired-test', ['*'], now()->subHour());

        // Create expired session
        VipSession::create([
            'vip_code_id' => $this->vipCode->id,
            'token' => 'expired_token_hash',
            'expires_at' => now()->subHour(),
            'sanctum_token_id' => $expiredToken->accessToken->id,
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
            ->and($result['vip_code']->code)->toBe('TESTCODE')
            ->and($result['is_demo'])->toBeFalse();
    });

    test('returns null for invalid token', function () {
        $result = $this->service->validateSessionToken('invalid_token');

        expect($result)->toBeNull();
    });

    test('returns null for expired token', function () {
        // Create expired token
        $expiredToken = $this->concierge->user->createToken('expired', ['*'], now()->subHour());
        VipSession::create([
            'vip_code_id' => $this->vipCode->id,
            'token' => hash('sha256', $expiredToken->plainTextToken),
            'sanctum_token_id' => $expiredToken->accessToken->id,
            'expires_at' => now()->subHour(),
        ]);

        $result = $this->service->validateSessionToken($expiredToken->plainTextToken);

        expect($result)->toBeNull();
    });

    test('returns null for non-VIP session token', function () {
        // Create regular Sanctum token not associated with VIP session
        $regularToken = $this->concierge->user->createToken('regular', ['*'], now()->addHour());

        $result = $this->service->validateSessionToken($regularToken->plainTextToken);

        expect($result)->toBeNull();
    });
});

describe('cleanupExpiredSessions', function () {
    test('cleans up expired sessions and tokens', function () {
        // Create expired sessions
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

        $cleanupCount = $this->service->cleanupExpiredSessions();

        expect($cleanupCount)->toBe(2);

        // Should have cleaned up expired sessions
        expect(VipSession::where('expires_at', '<', now())->count())->toBe(0);

        // Should have cleaned up expired Sanctum tokens
        expect(PersonalAccessToken::find($expiredToken1->accessToken->id))->toBeNull();
        expect(PersonalAccessToken::find($expiredToken2->accessToken->id))->toBeNull();
        expect(PersonalAccessToken::find($activeToken->accessToken->id))->not->toBeNull();

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
