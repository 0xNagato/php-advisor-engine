<?php

namespace App\Services;

use App\Data\GooglePlaceData;
use App\Models\Region;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private string $apiKey;

    private string $baseUrl = 'https://places.googleapis.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.google.places_api_key');
    }

    /**
     * Search for a place by name and address
     */
    public function searchPlace(string $query, ?string $region = null): ?GooglePlaceData
    {
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key is not configured');

            return null;
        }

        try {
            $body = [
                'textQuery' => $query,
            ];

            if ($region) {
                $regionData = Region::find($region);
                if ($regionData) {
                    $body['locationBias'] = [
                        'circle' => [
                            'center' => [
                                'latitude' => $regionData->lat,
                                'longitude' => $regionData->lon,
                            ],
                            'radius' => 50000, // 50km radius
                        ],
                    ];
                }
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.rating,places.priceLevel,places.userRatingCount,places.editorialSummary,places.generativeSummary,places.types,places.primaryType,places.servesCocktails,places.servesWine,places.servesBeer,places.outdoorSeating,places.liveMusic,places.photos',
            ])->post($this->baseUrl.'/places:searchText', $body);

            $data = $response->json();

            Log::info('Google Places API response', [
                'query' => $query,
                'places_count' => count($data['places'] ?? []),
                'error' => $data['error'] ?? null,
            ]);

            if ($response->successful() && isset($data['places'][0])) {
                $place = $data['places'][0];

                // Debug log the raw response
                Log::debug('Google Places raw response', [
                    'place' => $place,
                    'has_editorial_summary' => isset($place['editorialSummary']),
                    'has_generative_summary' => isset($place['generativeSummary']),
                ]);

                return GooglePlaceData::from([
                    'place_id' => str_replace('places/', '', $place['id'] ?? ''),
                    'name' => $place['displayName']['text'] ?? null,
                    'formatted_address' => $place['formattedAddress'] ?? null,
                    'rating' => $place['rating'] ?? null,
                    'price_level' => $this->convertPriceLevel($place['priceLevel'] ?? null),
                    'user_ratings_total' => $place['userRatingCount'] ?? null,
                    'editorial_summary' => $place['editorialSummary']['text'] ?? null,
                    'generative_summary' => $place['generativeSummary']['overview']['text'] ?? null,
                    'types' => $place['types'] ?? null,
                    'primary_type' => $place['primaryType'] ?? null,
                    'serves_cocktails' => $place['servesCocktails'] ?? null,
                    'serves_wine' => $place['servesWine'] ?? null,
                    'serves_beer' => $place['servesBeer'] ?? null,
                    'outdoor_seating' => $place['outdoorSeating'] ?? null,
                    'live_music' => $place['liveMusic'] ?? null,
                    'photos' => $this->processPhotos($place['photos'] ?? []),
                    'latitude' => $place['location']['latitude'] ?? null,
                    'longitude' => $place['location']['longitude'] ?? null,
                ]);
            }

            Log::warning('Google Places API search failed', [
                'query' => $query,
                'response' => $data,
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Google Places API error', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }

    /**
     * Get place details by place ID
     */
    public function getPlaceDetails(string $placeId): ?GooglePlaceData
    {
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key is not configured');

            return null;
        }

        try {
            // Ensure placeId has the 'places/' prefix
            if (! str_starts_with($placeId, 'places/')) {
                $placeId = 'places/'.$placeId;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'id,displayName,formattedAddress,location,rating,priceLevel,userRatingCount,editorialSummary,types,primaryType,servesCocktails,servesWine,servesBeer,outdoorSeating,liveMusic,photos',
            ])->get($this->baseUrl.'/'.$placeId);

            if ($response->successful()) {
                $place = $response->json();

                return GooglePlaceData::from([
                    'place_id' => str_replace('places/', '', $place['id'] ?? ''),
                    'name' => $place['displayName']['text'] ?? null,
                    'formatted_address' => $place['formattedAddress'] ?? null,
                    'rating' => $place['rating'] ?? null,
                    'price_level' => $this->convertPriceLevel($place['priceLevel'] ?? null),
                    'user_ratings_total' => $place['userRatingCount'] ?? null,
                    'editorial_summary' => $place['editorialSummary']['text'] ?? null,
                    'generative_summary' => $place['generativeSummary']['overview']['text'] ?? null,
                    'types' => $place['types'] ?? null,
                    'primary_type' => $place['primaryType'] ?? null,
                    'serves_cocktails' => $place['servesCocktails'] ?? null,
                    'serves_wine' => $place['servesWine'] ?? null,
                    'serves_beer' => $place['servesBeer'] ?? null,
                    'outdoor_seating' => $place['outdoorSeating'] ?? null,
                    'live_music' => $place['liveMusic'] ?? null,
                    'photos' => $this->processPhotos($place['photos'] ?? []),
                    'latitude' => $place['location']['latitude'] ?? null,
                    'longitude' => $place['location']['longitude'] ?? null,
                ]);
            }

            Log::warning('Google Places API details failed', [
                'place_id' => $placeId,
                'response' => $response->json(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Google Places API error', [
                'error' => $e->getMessage(),
                'place_id' => $placeId,
            ]);

            return null;
        }
    }

    /**
     * Convert Places API (New) price level to numeric value
     */
    private function convertPriceLevel(?string $priceLevel): ?int
    {
        if (! $priceLevel) {
            return null;
        }

        return match ($priceLevel) {
            'PRICE_LEVEL_INEXPENSIVE' => 1,
            'PRICE_LEVEL_MODERATE' => 2,
            'PRICE_LEVEL_EXPENSIVE' => 3,
            'PRICE_LEVEL_VERY_EXPENSIVE' => 4,
            default => null,
        };
    }

    /**
     * Process Google Places photos into high-resolution image URLs
     */
    private function processPhotos(array $photos): array
    {
        $imageUrls = [];

        // Limit to first 5 photos to avoid too many images
        $photos = array_slice($photos, 0, 5);

        foreach ($photos as $photo) {
            if (isset($photo['name'])) {
                // Create high-resolution photo URL using Google Places Photo API
                // Using 2000px max width for high quality images
                $photoUrl = "https://places.googleapis.com/v1/{$photo['name']}/media?key={$this->apiKey}&maxWidthPx=2000&maxHeightPx=1500";
                $imageUrls[] = $photoUrl;
            }
        }

        return $imageUrls;
    }
}
