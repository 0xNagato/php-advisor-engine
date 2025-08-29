<?php

use App\Data\GooglePlaceData;
use App\Data\VenueMetadata;
use App\Models\Venue;

test('venue metadata can be cast to data object', function () {
    $venue = Venue::factory()->create([
        'metadata' => [
            'rating' => 4.5,
            'priceLevel' => 3,
            'reviewCount' => 150,
            'googlePlaceId' => 'ChIJ1234567890',
            'lastSyncedAt' => now()->toISOString(),
        ],
    ]);

    expect($venue->metadata)->toBeInstanceOf(VenueMetadata::class);
    expect($venue->metadata->rating)->toBe(4.5);
    expect($venue->metadata->priceLevel)->toBe(3);
    expect($venue->metadata->reviewCount)->toBe(150);
    expect($venue->metadata->googlePlaceId)->toBe('ChIJ1234567890');
    expect($venue->metadata->lastSyncedAt)->not->toBeNull();
});

test('venue metadata returns null when not set', function () {
    $venue = Venue::factory()->create([
        'metadata' => null,
    ]);

    expect($venue->metadata)->toBeNull();
});

test('venue metadata display methods work correctly', function () {
    $venue = Venue::factory()->create([
        'metadata' => [
            'rating' => 4.2,
            'priceLevel' => 2,
        ],
    ]);

    expect($venue->metadata->getRatingDisplay())->toBe('4.2/5');
    expect($venue->metadata->getPriceLevelDisplay())->toBe('$$');
});

test('venue metadata display methods return null when values not set', function () {
    $venue = Venue::factory()->create([
        'metadata' => [],
    ]);

    expect($venue->metadata->getRatingDisplay())->toBeNull();
    expect($venue->metadata->getPriceLevelDisplay())->toBeNull();
});

test('metadata properties can be accessed directly', function () {
    $venue = Venue::factory()->create([
        'metadata' => [
            'rating' => 4.8,
            'priceLevel' => 4,
        ],
    ]);

    expect($venue->metadata->rating)->toBe(4.8);
    expect($venue->metadata->priceLevel)->toBe(4);
    expect($venue->metadata->reviewCount)->toBeNull();
});

test('metadata can be updated directly', function () {
    $venue = Venue::factory()->create();

    $venue->metadata = VenueMetadata::from([
        'rating' => 4.3,
        'priceLevel' => 2,
    ]);
    $venue->save();

    expect($venue->metadata->rating)->toBe(4.3);
    expect($venue->metadata->priceLevel)->toBe(2);
});

test('update metadata from google places data works', function () {
    $venue = Venue::factory()->create();

    $googleData = GooglePlaceData::from([
        'rating' => 4.7,
        'price_level' => 3,
        'user_ratings_total' => 234,
        'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
    ]);

    $venue->updateMetadataFromGoogle($googleData);
    $venue->save();

    expect($venue->metadata->rating)->toBe(4.7);
    expect($venue->metadata->priceLevel)->toBe(3);
    expect($venue->metadata->reviewCount)->toBe(234);
    expect($venue->metadata->googlePlaceId)->toBe('ChIJN1t_tDeuEmsRUsoyG83frY4');
    expect($venue->metadata->lastSyncedAt)->not->toBeNull();
});

test('price level display shows correct dollar signs', function () {
    $metadata = new VenueMetadata;

    $metadata->priceLevel = 1;
    expect($metadata->getPriceLevelDisplay())->toBe('$');

    $metadata->priceLevel = 2;
    expect($metadata->getPriceLevelDisplay())->toBe('$$');

    $metadata->priceLevel = 3;
    expect($metadata->getPriceLevelDisplay())->toBe('$$$');

    $metadata->priceLevel = 4;
    expect($metadata->getPriceLevelDisplay())->toBe('$$$$');
});
