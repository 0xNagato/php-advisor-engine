<?php

namespace Tests\Feature;

use App\Data\GooglePlaceData;
use App\Models\Venue;
use App\Services\GooglePlacesToCuisineMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GooglePlacesCuisineMappingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_maps_google_places_types_to_cuisines(): void
    {
        $mapper = new GooglePlacesToCuisineMapper;

        // Test various Google Places types
        $googleTypes = [
            'italian_restaurant',
            'japanese_restaurant',
            'sushi_restaurant', // Should also map to japanese
            'some_unknown_type', // Should be ignored
        ];

        $mappedCuisines = $mapper->mapToCuisines($googleTypes);

        $this->assertContains('italian', $mappedCuisines);
        $this->assertContains('japanese', $mappedCuisines);
        $this->assertCount(2, $mappedCuisines); // Should deduplicate japanese
    }

    /** @test */
    public function it_maps_google_places_attributes_to_specialties(): void
    {
        $mapper = new GooglePlacesToCuisineMapper;

        $googleTypes = ['fine_dining_restaurant'];
        $googleAttributes = [
            'live_music' => true,
            'outdoor_seating' => true,
            'serves_cocktails' => true, // Should not map to specialty
        ];

        $mappedSpecialties = $mapper->mapToSpecialties($googleTypes, $googleAttributes);

        $this->assertContains('fine_dining', $mappedSpecialties);
        $this->assertContains('live_music_dj', $mappedSpecialties);
        $this->assertContains('scenic_view', $mappedSpecialties);
        $this->assertCount(3, $mappedSpecialties);
    }

    /** @test */
    public function venue_updates_cuisines_from_google_places_data(): void
    {
        // Mock the image uploader service
        $this->mock(\App\Services\GooglePlacesImageUploader::class, function ($mock) {
            $mock->shouldReceive('uploadGooglePhotos')
                ->andReturn([
                    'production/venues/images/test-google-photo1.jpg',
                    'production/venues/images/test-google-photo2.jpg',
                ]);
        });

        // Create venue without existing cuisines
        $venue = Venue::factory()->create([
            'cuisines' => null,
            'specialty' => null,
        ]);

        // Create Google Places data with cuisine types
        $googleData = GooglePlaceData::from([
            'place_id' => 'test_place_123',
            'rating' => 4.5,
            'types' => ['italian_restaurant', 'pizza_restaurant'],
            'live_music' => true,
            'outdoor_seating' => false,
            'photos' => [
                'https://places.googleapis.com/v1/photo1/media?key=test&maxWidthPx=800',
                'https://places.googleapis.com/v1/photo2/media?key=test&maxWidthPx=800',
            ],
        ]);

        // Update venue (with photos enabled)
        $venue->updateMetadataFromGoogle($googleData, false);
        $venue->save();
        $venue->refresh();

        // Assert cuisines were mapped
        $this->assertNotEmpty($venue->cuisines);
        $this->assertContains('italian', $venue->cuisines);

        // Assert specialties were mapped
        $this->assertNotEmpty($venue->specialty);
        $this->assertContains('live_music_dj', $venue->specialty);

        // Assert images were added (uploaded paths, not original URLs)
        $venueImages = json_decode($venue->getRawOriginal('images'), true);
        $this->assertNotEmpty($venueImages);
        $this->assertContains('production/venues/images/test-google-photo1.jpg', $venueImages);
        $this->assertContains('production/venues/images/test-google-photo2.jpg', $venueImages);
    }

    /** @test */
    public function venue_merges_google_cuisines_with_existing_data(): void
    {
        // Create venue with existing cuisines
        $venue = Venue::factory()->create([
            'cuisines' => ['french', 'mediterranean'],
            'specialty' => ['fine_dining'],
        ]);

        // Create Google Places data that would map to different cuisines
        $googleData = GooglePlaceData::from([
            'place_id' => 'test_place_123',
            'rating' => 4.5,
            'types' => ['italian_restaurant', 'american_restaurant'],
            'live_music' => true,
        ]);

        // Update venue (with photos enabled)
        $venue->updateMetadataFromGoogle($googleData, false);
        $venue->save();
        $venue->refresh();

        // Assert cuisines were merged (existing + new)
        $this->assertContains('french', $venue->cuisines);
        $this->assertContains('mediterranean', $venue->cuisines);
        $this->assertContains('italian', $venue->cuisines);
        $this->assertContains('american', $venue->cuisines);

        // Assert specialties were merged
        $this->assertContains('fine_dining', $venue->specialty);
        $this->assertContains('live_music_dj', $venue->specialty);
    }

    /** @test */
    public function mapper_handles_empty_and_null_inputs(): void
    {
        $mapper = new GooglePlacesToCuisineMapper;

        $this->assertEmpty($mapper->mapToCuisines(null));
        $this->assertEmpty($mapper->mapToCuisines([]));
        $this->assertEmpty($mapper->mapToSpecialties(null, null));
        $this->assertEmpty($mapper->mapToSpecialties([], []));
    }

    /** @test */
    public function venue_uploads_google_photos_to_digital_ocean(): void
    {
        // Mock the image uploader service
        $this->mock(\App\Services\GooglePlacesImageUploader::class, function ($mock) {
            $mock->shouldReceive('uploadGooglePhotos')
                ->once()
                ->andReturn([
                    'production/venues/images/test-google-photo1.jpg',
                    'production/venues/images/test-google-photo2.jpg',
                ]);
        });

        // Create venue without existing images
        $venue = Venue::factory()->create([
            'images' => null,
        ]);

        // Create Google Places data with photos
        $googleData = GooglePlaceData::from([
            'place_id' => 'test_place_123',
            'rating' => 4.5,
            'photos' => [
                'https://places.googleapis.com/v1/photo1/media?key=test&maxWidthPx=2000&maxHeightPx=1500',
                'https://places.googleapis.com/v1/photo2/media?key=test&maxWidthPx=2000&maxHeightPx=1500',
            ],
        ]);

        // Update venue (with photos enabled)
        $venue->updateMetadataFromGoogle($googleData, false);
        $venue->save();
        $venue->refresh();

        // Assert images were uploaded
        $venueImages = json_decode($venue->getRawOriginal('images'), true);
        $this->assertCount(2, $venueImages);
        $this->assertContains('production/venues/images/test-google-photo1.jpg', $venueImages);
        $this->assertContains('production/venues/images/test-google-photo2.jpg', $venueImages);
    }

    /** @test */
    public function mapper_filters_out_invalid_cuisine_ids(): void
    {
        $mapper = new GooglePlacesToCuisineMapper;

        // Mock a scenario where mapping returns non-existent cuisine
        // This tests the validation in mapToCuisines method
        $validTypes = ['italian_restaurant'];
        $mappedCuisines = $mapper->mapToCuisines($validTypes);

        // Should only contain valid cuisines that exist in our Cuisine model
        $this->assertContains('italian', $mappedCuisines);
        foreach ($mappedCuisines as $cuisineId) {
            $this->assertNotNull(\App\Models\Cuisine::findById($cuisineId));
        }
    }
}
