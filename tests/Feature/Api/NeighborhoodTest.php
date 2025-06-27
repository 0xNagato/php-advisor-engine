<?php

use App\Models\Neighborhood;

use function Pest\Laravel\getJson;

test('can fetch all neighborhoods', function () {
    getJson('/api/neighborhoods')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);
});

test('can filter neighborhoods by region', function () {
    $region = 'miami';
    $miamiNeighborhoods = Neighborhood::query()->where('region', $region)->pluck('name', 'id')->toArray();

    $response = getJson('/api/neighborhoods?region='.$region)
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($miamiNeighborhoods);

    // Ensure only Miami neighborhoods are returned
    foreach ($responseData as $id => $name) {
        $neighborhood = Neighborhood::find($id);
        expect($neighborhood->region)->toBe($region);
    }
});
