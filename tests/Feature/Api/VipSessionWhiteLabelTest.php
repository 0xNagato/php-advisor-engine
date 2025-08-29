<?php

use App\Models\Concierge;
use App\Models\VipCode;

use function Pest\Laravel\postJson;

test('includes white-labeling information in VIP session response', function () {
    // Create a concierge with white-labeling configuration
    $concierge = Concierge::factory()->branded()->create();

    // Create a VIP code for this concierge
    $vipCode = VipCode::create([
        'code' => 'WHITELABEL',
        'concierge_id' => $concierge->id,
        'is_active' => true,
    ]);

    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'WHITELABEL',
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
                        'branding' => [
                            'brand_name',
                            'description',
                            'logo_url',
                            'main_color',
                            'secondary_color',
                            'gradient_start',
                            'gradient_end',
                            'text_color',
                            'redirect_url',
                        ],
                    ],
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'template' => 'availability_calendar',
                'vip_code' => [
                    'code' => 'WHITELABEL',
                    'concierge' => [
                        'branding' => [
                            'brand_name' => 'Sample Brand',
                            'description' => 'Welcome to our exclusive booking experience',
                            'main_color' => '#3B82F6',
                            'secondary_color' => '#1E40AF',
                            'gradient_start' => '#3B82F6',
                            'gradient_end' => '#1E40AF',
                            'text_color' => '#1F2937',
                            'redirect_url' => 'https://example.com/thank-you',
                        ],
                    ],
                ],
            ],
        ]);

    // Check that logo_url is an absolute URL
    $logoUrl = $response->json('data.vip_code.concierge.branding.logo_url');
    expect($logoUrl)->toBeString()
        ->and($logoUrl)->toStartWith('http');
});

test('handles concierge without white-labeling configuration', function () {
    // Create a concierge without white-labeling configuration
    $concierge = Concierge::factory()->create();

    // Create a VIP code for this concierge
    $vipCode = VipCode::create([
        'code' => 'NOLABEL',
        'concierge_id' => $concierge->id,
        'is_active' => true,
    ]);

    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'NOLABEL',
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
                    'code' => 'NOLABEL',
                ],
            ],
        ]);

    // Verify that branding is not present in the response
    $responseData = $response->json('data.vip_code.concierge');
    expect($responseData)->not->toHaveKey('branding');
});
