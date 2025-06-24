<?php

use function Pest\Laravel\getJson;

test('can fetch timeslots', function () {
    $date = now()->format('Y-m-d');

    getJson("/api/timeslots?date={$date}")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('timeslots can be filtered by date', function () {
    $date = now()->format('Y-m-d');

    getJson("/api/timeslots?date={$date}")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('can fetch timeslots with region parameter', function () {
    $date = now()->addDay()->format('Y-m-d');

    // Test with a specific region
    getJson("/api/timeslots?date={$date}&region=miami")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    // Test with a different region
    getJson("/api/timeslots?date={$date}&region=paris")
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('timeslots endpoint rejects invalid region parameter', function () {
    $date = now()->addDay()->format('Y-m-d');

    getJson("/api/timeslots?date={$date}&region=invalid_region")
        ->assertStatus(422)
        ->assertJson([
            'region' => [
                'The selected region is invalid.',
            ],
        ]);
});
