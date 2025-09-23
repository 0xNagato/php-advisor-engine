<?php

use App\Models\VipCode;
use App\Models\VipLinkHit;
use App\Models\VipSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('VIP landing captures query params including arrays', function () {
    $vipCode = VipCode::factory()->create(['code' => 'MIAMI2024', 'is_active' => true]);

    $response = $this->get('/v/MIAMI2024?name=dru&cuisine[]=japanese&cuisine[]=chinese&utm_source=facebook&guest_count=4');

    $response->assertRedirect();

    $hit = VipLinkHit::query()->first();
    expect($hit)->not->toBeNull();
    expect($hit->vip_code_id)->toBe($vipCode->id);
    expect($hit->code)->toBe('MIAMI2024');
    expect($hit->query_params)->toHaveKey('name');
    expect($hit->query_params['name'])->toBe('dru');
    expect($hit->query_params)->toHaveKey('cuisine');
    expect($hit->query_params['cuisine'])->toBe(['japanese', 'chinese']);
    expect($hit->query_params)->toHaveKey('utm_source');
    expect($hit->query_params['utm_source'])->toBe('facebook');
    expect($hit->query_params)->toHaveKey('guest_count');
    expect($hit->query_params['guest_count'])->toBe('4');
    expect($hit->raw_query)->toContain('name=dru');
    expect($hit->raw_query)->toContain('cuisine%5B');
    expect($hit->full_url)->toContain('/v/MIAMI2024');
});

test('VIP landing captures with invalid VIP code', function () {
    $response = $this->get('/v/INVALID?name=test');

    $response->assertRedirect();

    $hit = VipLinkHit::query()->first();
    expect($hit)->not->toBeNull();
    expect($hit->vip_code_id)->toBeNull();
    expect($hit->code)->toBe('INVALID');
    expect($hit->query_params)->toHaveKey('name');
    expect($hit->query_params['name'])->toBe('test');
});

test('VIP landing captures without query params', function () {
    $vipCode = VipCode::factory()->create(['code' => 'SIMPLE', 'is_active' => true]);

    $response = $this->get('/v/SIMPLE');

    $response->assertRedirect();

    $hit = VipLinkHit::query()->first();
    expect($hit)->not->toBeNull();
    expect($hit->vip_code_id)->toBe($vipCode->id);
    expect($hit->query_params)->toBe([]);
    expect($hit->raw_query)->toBeNull();
});

test('VIP landing preserves redirect behavior', function () {
    VipCode::factory()->create(['code' => 'TEST', 'is_active' => true]);

    $response = $this->get('/v/TEST?param=value');

    $expectedUrl = config('app.booking_url').'/vip/TEST?param=value';
    $response->assertRedirect($expectedUrl);
});

test('VIP session creation accepts and persists query params', function () {
    $vipCode = VipCode::factory()->create(['code' => 'API2024', 'is_active' => true]);

    $queryParams = [
        'source' => 'instagram',
        'campaign' => 'summer2024',
        'tags' => ['food', 'nightlife'],
        'guest_count' => 6,
    ];

    $response = $this->postJson('/api/vip/sessions', [
        'vip_code' => 'API2024',
        'query_params' => $queryParams,
    ]);

    $response->assertSuccessful();

    $session = VipSession::query()->first();
    expect($session)->not->toBeNull();
    expect($session->vip_code_id)->toBe($vipCode->id);
    expect($session->query_params)->toHaveKeys(['source', 'campaign', 'tags', 'guest_count']);
    expect($session->query_params['source'])->toBe('instagram');
    expect($session->query_params['campaign'])->toBe('summer2024');
    expect($session->query_params['tags'])->toBe(['food', 'nightlife']);
    expect($session->query_params['guest_count'])->toBe(6);
});

test('VIP session creation works without query params', function () {
    VipCode::factory()->create(['code' => 'SIMPLE2024', 'is_active' => true]);

    $response = $this->postJson('/api/vip/sessions', [
        'vip_code' => 'SIMPLE2024',
    ]);

    $response->assertSuccessful();

    $session = VipSession::query()->first();
    expect($session)->not->toBeNull();
    expect($session->query_params)->toBeNull();
});

test('VIP landing tracking does not break on exception', function () {
    // Simulate a scenario where tracking might fail but redirect should still work
    $response = $this->get('/v/NONEXISTENT?param=value');

    // Should still redirect even if VIP code doesn't exist
    $response->assertRedirect();

    // But should still capture the hit
    $hit = VipLinkHit::query()->first();
    expect($hit)->not->toBeNull();
    expect($hit->code)->toBe('NONEXISTENT');
});

test('VIP link hit truncates very long query values', function () {
    $longValue = str_repeat('a', 2000);
    VipCode::factory()->create(['code' => 'TRUNCATE', 'is_active' => true]);

    $response = $this->get("/v/TRUNCATE?long_param={$longValue}");

    $response->assertRedirect();

    $hit = VipLinkHit::query()->first();
    expect($hit->query_params['long_param'])->toHaveLength(1000);
    expect($hit->query_params['long_param'])->toBe(str_repeat('a', 1000));
});

test('VIP session validates query params must be array if provided', function () {
    VipCode::factory()->create(['code' => 'VALIDATE', 'is_active' => true]);

    $response = $this->postJson('/api/vip/sessions', [
        'vip_code' => 'VALIDATE',
        'query_params' => 'invalid_string',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['query_params']);
});

test('VIP link hit preserves referer and user agent', function () {
    $vipCode = VipCode::factory()->create(['code' => 'REFERER', 'is_active' => true]);

    $response = $this->get('/v/REFERER?name=test', [
        'Referer' => 'https://example.com/source',
        'User-Agent' => 'TestAgent/1.0',
    ]);

    $response->assertRedirect();

    $hit = VipLinkHit::query()->first();
    expect($hit)->not->toBeNull();
    expect($hit->referer_url)->toBe('https://example.com/source');
    expect($hit->user_agent)->toBe('TestAgent/1.0');
    expect($hit->ip_address)->not->toBeNull();
});

test('VIP session includes landing and referer URLs', function () {
    $vipCode = VipCode::factory()->create(['code' => 'SESSIONURL', 'is_active' => true]);

    $response = $this->postJson('/api/vip/sessions', [
        'vip_code' => 'SESSIONURL',
        'query_params' => ['source' => 'test'],
    ], [
        'Referer' => 'https://example.com/referer',
    ]);

    $response->assertSuccessful();

    $session = VipSession::query()->first();
    expect($session)->not->toBeNull();
    expect($session->referer_url)->toBe('https://example.com/referer');
    expect($session->landing_url)->toBe('https://prima.test/api/vip/sessions');
});
