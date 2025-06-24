<?php

use App\Models\Specialty;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();
    $this->user->assignRole('user');

    // Create an authentication token
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('unauthenticated user can access specialties', function () {
    $allSpecialties = Specialty::query()->pluck('name', 'id')->toArray();

    $response = getJson('/api/specialties')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($allSpecialties);
});

test('authenticated user can fetch all specialties', function () {
    $allSpecialties = Specialty::query()->pluck('name', 'id')->toArray();

    $response = getJson('/api/specialties', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
        ]);

    $responseData = $response->json('data');
    expect($responseData)->toBe($allSpecialties);
});

test('authenticated user can filter specialties by region', function () {
    $region = 'miami';
    $miamiSpecialties = Specialty::getSpecialtiesByRegion($region)->toArray();

    $response = getJson('/api/specialties?region='.$region, [
        'Authorization' => 'Bearer '.$this->token,
    ])
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
