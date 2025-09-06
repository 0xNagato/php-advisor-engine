<?php

use App\Models\Region;
use App\Models\User;
use App\Models\Venue;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('venue index returns metadata fields', function () {
    $region = Region::first();
    $venue = Venue::factory()->create([
        'region' => $region->id,
        'status' => 'active',
        'metadata' => [
            'rating' => 4.5,
            'priceLevel' => 3,
            'reviewCount' => 150,
            'googlePlaceId' => 'ChIJ1234567890',
            'lastSyncedAt' => now()->toISOString(),
        ],
    ]);

    $response = $this->getJson('/api/venues', [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'rating',
                    'price_level',
                    'price_level_display',
                    'rating_display',
                    'review_count',
                ],
            ],
        ])
        ->assertJsonPath('data.0.rating', 4.5)
        ->assertJsonPath('data.0.price_level', 3)
        ->assertJsonPath('data.0.price_level_display', '$$$')
        ->assertJsonPath('data.0.rating_display', '4.5/5')
        ->assertJsonPath('data.0.review_count', 150);
});

test('venue show returns metadata fields', function () {
    $venue = Venue::factory()->create([
        'metadata' => [
            'rating' => 4.2,
            'priceLevel' => 2,
            'reviewCount' => 87,
            'googlePlaceId' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
            'lastSyncedAt' => now()->toISOString(),
        ],
    ]);

    $response = $this->getJson("/api/venues/{$venue->id}", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonFragment([
            'rating' => 4.2,
            'price_level' => 2,
            'price_level_display' => '$$',
            'rating_display' => '4.2/5',
            'review_count' => 87,
            'google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
        ]);
});

test('venue without metadata returns null values', function () {
    $venue = Venue::factory()->withoutMetadata()->create();

    $response = $this->getJson("/api/venues/{$venue->id}", [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonFragment([
            'rating' => null,
            'price_level' => null,
            'price_level_display' => null,
            'rating_display' => null,
            'review_count' => null,
            'google_place_id' => null,
        ]);
});

test('availability calendar includes metadata in venue resource', function () {
    $region = Region::first();
    $venue = Venue::factory()->create([
        'region' => $region->id,
        'status' => 'active',
        'metadata' => [
            'rating' => 4.7,
            'priceLevel' => 4,
            'reviewCount' => 500,
        ],
    ]);

    $response = $this->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00',
        'region' => $region->id,
    ]), [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.venues.0.rating', 4.7)
        ->assertJsonPath('data.venues.0.price_level', 4)
        ->assertJsonPath('data.venues.0.price_level_display', '$$$$')
        ->assertJsonPath('data.venues.0.rating_display', '4.7/5')
        ->assertJsonPath('data.venues.0.review_count', 500);
});
