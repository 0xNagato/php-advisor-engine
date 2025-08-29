<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\OpenApi\Responses\ShowVenueResponse;
use App\OpenApi\Responses\VenueListResponse;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;
use App\Models\Cuisine;
use App\Models\Specialty;

#[OpenApi\PathItem]
class VenueController extends Controller
{
    /**
     * Retrieve available venues in the current region.
     */
    #[OpenApi\Operation(
        tags: ['Venues'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[OpenApiResponse(factory: VenueListResponse::class)]
    public function index(): JsonResponse
    {
        $region = GetUserRegion::run();

        // Get all available venues in the region
        $query = Venue::query()
            ->where('region', $region->id)
            ->where('status', VenueStatus::ACTIVE);

        // Filter by concierge's allowed venues if applicable
        if (auth()->check() && auth()->user()->hasActiveRole('concierge') && auth()->user()->concierge) {
            $allowedVenueIds = auth()->user()->concierge->allowed_venue_ids ?? [];

            // Only apply the filter if there are allowed venues
            if (filled($allowedVenueIds)) {
                // Ensure all IDs are integers
                $allowedVenueIds = array_map('intval', $allowedVenueIds);
                $query->whereIn('id', $allowedVenueIds);
            }
        }

        $venues = $query->orderBy('name')->get(['id', 'name', 'metadata']);

        return response()->json([
            'data' => $venues->map(function ($venue) {
                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'rating' => $venue->metadata?->rating,
                    'price_level' => $venue->metadata?->priceLevel,
                    'price_level_display' => $venue->metadata?->getPriceLevelDisplay(),
                    'rating_display' => $venue->metadata?->getRatingDisplay(),
                    'review_count' => $venue->metadata?->reviewCount,
                ];
            }),
        ]);
    }

    /**
     * View a venue by ID.
     */
    #[OpenApi\Operation(
        tags: ['Venues'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[OpenApiResponse(factory: ShowVenueResponse::class)]
    public function show(int $id): JsonResponse
    {
        $venue = Venue::query()->find($id);

        if (! $venue) {
            return response()->json([
                'message' => 'Venue not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'address' => $venue->address,
                'description' => $venue->description,
                'images' => $venue->images ?? [],
                'logo' => $venue->logo,
                'cuisines' => $this->formatCuisines($venue->cuisines ?? []),
                'specialty' => $this->formatSpecialties($venue->specialty ?? []),
                'neighborhood' => $venue->neighborhood,
                'region' => $venue->region,
                'status' => $venue->status->value,
                'formatted_location' => $venue->getFormattedLocation(),
                'rating' => $venue->metadata?->rating,
                'price_level' => $venue->metadata?->priceLevel,
                'price_level_display' => $venue->metadata?->getPriceLevelDisplay(),
                'rating_display' => $venue->metadata?->getRatingDisplay(),
                'review_count' => $venue->metadata?->reviewCount,
                'google_place_id' => $venue->metadata?->googlePlaceId,
            ],
        ]);
    }

    /**
     * Format cuisines as key-value pairs
     */
    private function formatCuisines(array $cuisines): array
    {
        $formatted = [];
        $cuisinesList = Cuisine::getNamesList();
        
        foreach ($cuisines as $cuisineId) {
            if (isset($cuisinesList[$cuisineId])) {
                $formatted[] = [
                    'id' => $cuisineId,
                    'name' => $cuisinesList[$cuisineId]
                ];
            }
        }
        
        return $formatted;
    }

    /**
     * Format specialties as key-value pairs
     */
    private function formatSpecialties(array $specialties): array
    {
        $formatted = [];
        $specialtiesList = Specialty::query()->pluck('name', 'id');
        
        foreach ($specialties as $specialtyId) {
            if (isset($specialtiesList[$specialtyId])) {
                $formatted[] = [
                    'id' => $specialtyId,
                    'name' => $specialtiesList[$specialtyId]
                ];
            }
        }
        
        return $formatted;
    }
}
