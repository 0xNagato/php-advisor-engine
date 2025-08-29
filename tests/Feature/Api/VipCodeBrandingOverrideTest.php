<?php

use App\Data\AffiliateBrandingData;
use App\Enums\VipCodeTemplate;
use App\Models\Concierge;
use App\Models\VipCode;

use function Pest\Laravel\postJson;

test('vip code branding overrides concierge branding in api response', function () {
    // Create a concierge with branding
    $concierge = Concierge::factory()->create([
        'branding' => AffiliateBrandingData::from([
            'brand_name' => 'Concierge Brand',
            'description' => 'Concierge description',
            'main_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'template' => VipCodeTemplate::AVAILABILITY_CALENDAR,
        ]),
    ]);

    // Create a VIP code with its own branding that should override concierge branding
    $vipCode = VipCode::create([
        'code' => 'OVERRIDE',
        'concierge_id' => $concierge->id,
        'is_active' => true,
        'branding' => AffiliateBrandingData::from([
            'brand_name' => 'VIP Code Brand',
            'description' => 'VIP Code description',
            'main_color' => '#0000FF',
            'secondary_color' => '#FFFF00',
            'template' => VipCodeTemplate::AVAILABILITY_CALENDAR,
        ]),
    ]);

    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'OVERRIDE',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'session_token',
                'expires_at',
                'template',
                'flow_type',
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
                            'main_color',
                            'secondary_color',
                            'template',
                        ],
                    ],
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'flow_type' => 'white_label',
                'template' => VipCodeTemplate::AVAILABILITY_CALENDAR->value,
                'vip_code' => [
                    'code' => 'OVERRIDE',
                    'concierge' => [
                        // Should return VIP code branding, not concierge branding
                        'branding' => [
                            'brand_name' => 'VIP Code Brand',
                            'description' => 'VIP Code description',
                            'main_color' => '#0000FF',
                            'secondary_color' => '#FFFF00',
                            'template' => VipCodeTemplate::AVAILABILITY_CALENDAR->value,
                        ],
                    ],
                ],
            ],
        ]);
});

test('falls back to concierge branding when vip code has no branding', function () {
    // Create a concierge with branding
    $concierge = Concierge::factory()->create([
        'branding' => AffiliateBrandingData::from([
            'brand_name' => 'Fallback Brand',
            'description' => 'Fallback description',
            'main_color' => '#888888',
            'template' => VipCodeTemplate::AVAILABILITY_CALENDAR,
        ]),
    ]);

    // Create a VIP code without branding
    $vipCode = VipCode::create([
        'code' => 'FALLBACK',
        'concierge_id' => $concierge->id,
        'is_active' => true,
        'branding' => null,
    ]);

    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'FALLBACK',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'flow_type' => 'white_label',
                'vip_code' => [
                    'code' => 'FALLBACK',
                    'concierge' => [
                        // Should return concierge branding
                        'branding' => [
                            'brand_name' => 'Fallback Brand',
                            'description' => 'Fallback description',
                            'main_color' => '#888888',
                        ],
                    ],
                ],
            ],
        ]);
});

test('returns standard flow when neither vip code nor concierge has branding', function () {
    // Create a concierge without branding
    $concierge = Concierge::factory()->create();

    // Create a VIP code without branding
    $vipCode = VipCode::create([
        'code' => 'NOBRANDING',
        'concierge_id' => $concierge->id,
        'is_active' => true,
        'branding' => null,
    ]);

    $response = postJson('/api/vip/sessions', [
        'vip_code' => 'NOBRANDING',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'flow_type' => 'standard',
                'template' => VipCodeTemplate::AVAILABILITY_CALENDAR->value,
                'vip_code' => [
                    'code' => 'NOBRANDING',
                    'concierge' => [
                        'id' => $concierge->id,
                        'name' => $concierge->user->name,
                        'hotel_name' => $concierge->hotel_name,
                        // No branding key should be present
                    ],
                ],
            ],
        ]);

    // Verify that branding is not present in the response
    $responseData = $response->json('data.vip_code.concierge');
    expect($responseData)->not->toHaveKey('branding');
});
