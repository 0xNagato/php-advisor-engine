<?php

use App\Models\Venue;
use App\Services\ReservationService;

it('returns venues in tier ordered by position then name for null positions', function () {

    // Create venues with explicit ordering
    $venueB = Venue::factory()->create([
        'name' => 'Bravo',
        'region' => 'miami',
        'tier' => 1,
        'tier_position' => 1,
    ]);

    $venueA = Venue::factory()->create([
        'name' => 'Alpha',
        'region' => 'miami',
        'tier' => 1,
        'tier_position' => 2,
    ]);

    $venueC = Venue::factory()->create([
        'name' => 'Charlie',
        'region' => 'miami',
        'tier' => 1,
        'tier_position' => null,
    ]);

    // Another region venue should not be included
    Venue::factory()->create([
        'name' => 'Delta',
        'region' => 'ibiza',
        'tier' => 1,
        'tier_position' => 1,
    ]);

    $ids = ReservationService::getVenuesInTier('miami', 1);

    expect($ids)->toBe([$venueB->id, $venueA->id, $venueC->id]);
});
