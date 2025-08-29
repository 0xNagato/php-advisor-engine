<?php

namespace Tests\Feature;

use App\Data\GooglePlaceData;
use App\Data\VenueMetadata;
use App\Models\Venue;
use App\Services\GooglePlacesService;
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
        $this->assertEquals(
            'A wonderful test restaurant serving delicious imaginary food.',
            $venue->metadata->googleDescription
        );
        $this->assertNotNull($venue->metadata->lastSyncedAt);
    }

    /** @test */
    public function google_places_service_includes_editorial_summary_in_search(): void
    {
        // Mock the HTTP client
        $this->mock(\Illuminate\Http\Client\Factory::class, function ($mock) {
            $mock->shouldReceive('withHeaders')->andReturnSelf();
            $mock->shouldReceive('post')->andReturn(
                new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                        'places' => [[
                            'id' => 'places/test_123',
                            'displayName' => ['text' => 'Test Place'],
                            'formattedAddress' => '123 Test St',
                            'rating' => 4.5,
                            'priceLevel' => 'PRICE_LEVEL_EXPENSIVE',
                            'userRatingCount' => 100,
                            'editorialSummary' => ['text' => 'Test description from Google'],
                        ]],
                    ]))
                )
            );
        });

        $service = new GooglePlacesService;
        $result = $service->searchPlace('Test Place');

        $this->assertNotNull($result);
        $this->assertEquals('Test description from Google', $result->editorialSummary);
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
