<?php

use App\Models\VipCode;
use App\Models\VipSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('VIP landing creates session with query params including arrays', function () {
    $vipCode = VipCode::factory()->create(['code' => 'MIAMI2024', 'is_active' => true]);

    $response = $this->get('/v/MIAMI2024?name=dru&cuisine[]=japanese&cuisine[]=chinese&utm_source=facebook&guest_count=4');

    $response->assertRedirect();

    // Sessions are created via API, so we check the redirect URL has query params preserved
    $response->assertRedirectContains('name=dru');
    $response->assertRedirectContains('utm_source=facebook');
});

test('VIP landing with invalid VIP code still preserves query params', function () {
    $response = $this->get('/v/INVALID?name=test');

    $response->assertRedirect();
    // Query params are preserved in redirect even for invalid codes
    $response->assertRedirectContains('name=test');
});

test('VIP landing without query params', function () {
    $vipCode = VipCode::factory()->create(['code' => 'SIMPLE', 'is_active' => true]);

    $response = $this->get('/v/SIMPLE');

    $response->assertRedirect();
    // Should redirect to the frontend with the VIP code
    $response->assertRedirectContains('/vip/SIMPLE');
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

test('VIP landing with nonexistent code still redirects', function () {
    // Simulate a scenario where VIP code doesn't exist
    $response = $this->get('/v/NONEXISTENT?param=value');

    // Should still redirect even if VIP code doesn't exist
    $response->assertRedirect();
    // Query params are preserved
    $response->assertRedirectContains('param=value');
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

test('CreateBooking action links booking to VIP session when vipSessionId provided', function () {
    $concierge = \App\Models\Concierge::factory()->create();
    $venue = \App\Models\Venue::factory()->create([
        'region' => 'miami',
        'status' => \App\Enums\VenueStatus::ACTIVE,
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
    ]);
    $vipCode = \App\Models\VipCode::factory()->create([
        'code' => 'LINKTEST',
        'is_active' => true,
        'concierge_id' => $concierge->id,
    ]);
    $scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $venue->id,
        'day_of_week' => 'friday',
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
        'prime_time' => false,
        'is_available' => true,
    ]);

    // Create VIP session with query parameters
    $vipSession = \App\Models\VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => [
            'utm_source' => 'facebook',
            'utm_campaign' => 'summer2024',
            'guest_count' => 4,
        ],
    ]);

    // Create booking using CreateBooking action with VIP session ID
    $result = \App\Actions\Booking\CreateBooking::run(
        scheduleTemplateId: $scheduleTemplate->id,
        data: [
            'date' => now()->addDay()->format('Y-m-d'),
            'guest_count' => 4,
        ],
        vipCode: $vipCode,
        source: 'test',
        device: 'web',
        vipSessionId: $vipSession->id
    );

    // Verify booking is linked to VIP session
    expect($result->booking->vip_session_id)->toBe($vipSession->id);
    expect($result->booking->vip_code_id)->toBe($vipCode->id);
    expect($result->booking->concierge_id)->toBe($concierge->id);

    // Verify we can access session query parameters through booking
    $booking = $result->booking->load('vipSession');
    expect($booking->vipSession->query_params['utm_source'])->toBe('facebook');
    expect($booking->vipSession->query_params['utm_campaign'])->toBe('summer2024');
});

test('VIP session relationship on booking model works correctly', function () {
    \App\Models\Booking::withoutEvents(function () {
        $concierge = \App\Models\Concierge::factory()->create();
        $venue = \App\Models\Venue::factory()->create(['non_prime_fee_per_head' => 10]);
        $vipCode = \App\Models\VipCode::factory()->create(['concierge_id' => $concierge->id]);
        $vipSession = \App\Models\VipSession::factory()->create([
            'vip_code_id' => $vipCode->id,
            'query_params' => ['utm_source' => 'instagram'],
        ]);

        $booking = \App\Models\Booking::factory()->create([
            'vip_session_id' => $vipSession->id,
            'vip_code_id' => $vipCode->id,
            'concierge_id' => $concierge->id,
            'schedule_template_id' => \App\Models\ScheduleTemplate::factory()->create(['venue_id' => $venue->id])->id,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'total_fee' => 1000,
            'booking_at' => now()->addDay(),
        ]);

        // Test relationship loading
        $booking->load('vipSession');
        expect($booking->vipSession)->not->toBeNull();
        expect($booking->vipSession->id)->toBe($vipSession->id);
        expect($booking->vipSession->query_params['utm_source'])->toBe('instagram');
    });
});

test('booking to VIP session attribution enables marketing analysis', function () {
    \App\Models\Booking::withoutEvents(function () {
        $concierge = \App\Models\Concierge::factory()->create();
        $venue = \App\Models\Venue::factory()->create(['non_prime_fee_per_head' => 10]);
        $vipCode = \App\Models\VipCode::factory()->create([
            'code' => 'ANALYTICS',
            'concierge_id' => $concierge->id,
        ]);
        $vipSession = \App\Models\VipSession::factory()->create([
            'vip_code_id' => $vipCode->id,
            'query_params' => [
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'holiday2024',
                'utm_content' => 'ad_12345',
                'cuisine' => ['italian', 'seafood'],
                'guest_count' => 6,
                'budget' => 250,
            ],
        ]);

        $booking = \App\Models\Booking::factory()->create([
            'vip_session_id' => $vipSession->id,
            'vip_code_id' => $vipCode->id,
            'concierge_id' => $concierge->id,
            'status' => \App\Enums\BookingStatus::CONFIRMED,
            'total_fee' => 15000, // $150
            'schedule_template_id' => \App\Models\ScheduleTemplate::factory()->create(['venue_id' => $venue->id])->id,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'booking_at' => now()->addDay(),
        ]);

        // Verify complete attribution chain
        $booking->load('vipSession.vipCode');

        // Marketing attribution data available
        expect($booking->vipSession->query_params['utm_source'])->toBe('google');
        expect($booking->vipSession->query_params['utm_campaign'])->toBe('holiday2024');

        // Customer preference data available
        expect($booking->vipSession->query_params['cuisine'])->toBe(['italian', 'seafood']);
        expect($booking->vipSession->query_params['guest_count'])->toBe(6);
        expect($booking->vipSession->query_params['budget'])->toBe(250);

        // Revenue attribution possible
        expect($booking->total_fee)->toBe(15000);
        expect($booking->status)->toBe(\App\Enums\BookingStatus::CONFIRMED);

        // Complete tracking chain verified
        expect($booking->vipCode->code)->toBe('ANALYTICS');
        expect($booking->vipSession->vip_code_id)->toBe($vipCode->id);
    });
});
