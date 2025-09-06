<?php

namespace Tests\Feature;

use App\Data\GooglePlaceData;
use App\Data\VenueMetadata;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueGoogleDescriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_stores_google_description_in_venue_metadata(): void
    {
        // Create a venue
        $venue = Venue::factory()->create([
            'name' => 'Test Restaurant',
            'address' => '123 Test St',
            'metadata' => null,
        ]);

        // Create mock Google Place data with description
        $googleData = GooglePlaceData::from([
            'place_id' => 'test_place_123',
            'name' => 'Test Restaurant',
            'rating' => 4.5,
            'price_level' => 3,
            'user_ratings_total' => 150,
            'editorial_summary' => 'A wonderful test restaurant serving delicious imaginary food.',
        ]);

        // Update venue metadata
        $venue->updateMetadataFromGoogle($googleData);
        $venue->save();

        // Reload venue
        $venue->refresh();

        // Assert metadata is stored correctly
        $this->assertInstanceOf(VenueMetadata::class, $venue->metadata);
        $this->assertEquals(4.5, $venue->metadata->rating);
        $this->assertEquals(3, $venue->metadata->priceLevel);
        $this->assertEquals(150, $venue->metadata->reviewCount);
        $this->assertEquals('test_place_123', $venue->metadata->googlePlaceId);
        // We no longer sync descriptions from Google Places API
        $this->assertNull($venue->metadata->googleDescription);
        $this->assertNotNull($venue->metadata->lastSyncedAt);
    }

    /** @test */
    public function it_preserves_existing_description_when_google_returns_null(): void
    {
        // Create a venue with existing metadata including description
        $venue = Venue::factory()->create([
            'name' => 'Test Restaurant',
            'metadata' => VenueMetadata::from([
                'rating' => 4.0,
                'priceLevel' => 2,
                'googleDescription' => 'Existing description',
            ]),
        ]);

        // Create Google data without description
        $googleData = GooglePlaceData::from([
            'place_id' => 'test_place_123',
            'rating' => 4.5,
            'editorial_summary' => null, // No description from Google
        ]);

        // Update venue
        $venue->updateMetadataFromGoogle($googleData);
        $venue->save();
        $venue->refresh();

        // Assert existing description is preserved
        $this->assertEquals('Existing description', $venue->metadata->googleDescription);
        $this->assertEquals(4.5, $venue->metadata->rating); // But rating should be updated
    }
}
