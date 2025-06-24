<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
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

        $venues = $query->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $venues,
        ]);
    }

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
                'cuisines' => $venue->cuisines ?? [],
                'specialty' => $venue->specialty ?? [],
                'neighborhood' => $venue->neighborhood,
                'region' => $venue->region,
                'status' => $venue->status->value,
                'formatted_location' => $venue->getFormattedLocation(),
            ],
        ]);
    }
}
