<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\OpenApi\Responses\VenueListResponse;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class VenueController extends Controller
{
    /**
     * Retrieve available venues in the current region.
     */
    #[OpenApi\Operation(
        tags: ['Venues'],
    )]
    #[OpenApiResponse(factory: VenueListResponse::class)]
    public function __invoke(): JsonResponse
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

        $venues = $query->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $venues,
        ]);
    }
}
