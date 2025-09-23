<?php

use App\Data\AffiliateBrandingData;
use App\Enums\VipCodeTemplate;

test('affiliate branding data includes influencer metadata', function () {
    $brandingData = new AffiliateBrandingData(
        brand_name: 'Test Brand',
        description: 'Test Description',
        logo_url: 'https://example.com/logo.png',
        main_color: '#3B82F6',
        secondary_color: '#1E40AF',
        gradient_start: '#3B82F6',
        gradient_end: '#1E40AF',
        text_color: '#1F2937',
        redirect_url: 'https://example.com',
        template: VipCodeTemplate::AVAILABILITY_CALENDAR,
        influencer_name: 'John Doe',
        influencer_handle: 'johndoe',
        follower_count: '12.5K',
        social_url: 'https://instagram.com/johndoe'
    );

    expect($brandingData->influencer_name)->toBe('John Doe');
    expect($brandingData->influencer_handle)->toBe('johndoe');
    expect($brandingData->follower_count)->toBe('12.5K');
    expect($brandingData->social_url)->toBe('https://instagram.com/johndoe');
});

test('affiliate branding data API response includes influencer fields', function () {
    $brandingData = new AffiliateBrandingData(
        brand_name: 'Test Brand',
        influencer_name: 'Jane Smith',
        influencer_handle: 'janesmith',
        follower_count: '1M',
        social_url: 'https://tiktok.com/@janesmith'
    );

    $apiResponse = $brandingData->toApiResponse();

    expect($apiResponse)->toHaveKey('influencer_name', 'Jane Smith');
    expect($apiResponse)->toHaveKey('influencer_handle', 'janesmith');
    expect($apiResponse)->toHaveKey('follower_count', '1M');
    expect($apiResponse)->toHaveKey('social_url', 'https://tiktok.com/@janesmith');
});

test('hasBranding returns true when influencer metadata is present', function () {
    $brandingData = new AffiliateBrandingData(
        influencer_name: 'Influencer Name',
        follower_count: '50K'
    );

    expect($brandingData->hasBranding())->toBeTrue();
});

test('fromDefaults includes null influencer fields', function () {
    $brandingData = AffiliateBrandingData::fromDefaults();

    expect($brandingData->influencer_name)->toBeNull();
    expect($brandingData->influencer_handle)->toBeNull();
    expect($brandingData->follower_count)->toBeNull();
    expect($brandingData->social_url)->toBeNull();
});
