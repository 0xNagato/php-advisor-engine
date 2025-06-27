<?php

use App\Models\Specialty;

use function Pest\Laravel\getJson;

test('can fetch all specialties', function () {
    $allSpecialties = Specialty::query()->pluck('name', 'id')->toArray();

    $response = getJson('/api/specialties')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($allSpecialties);
});

test('can filter specialties by region', function () {
    $region = 'miami';
    $miamiSpecialties = Specialty::getSpecialtiesByRegion($region)->toArray();

    $response = getJson('/api/specialties?region='.$region)
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($miamiSpecialties);

    // Ensure all specialties in the response support the specified region
    foreach ($responseData as $id => $name) {
        $specialty = Specialty::find($id);
        $regions = explode(',', $specialty->regions);
        expect($regions)->toContain($region);
    }
});
