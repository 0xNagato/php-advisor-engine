<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $region = GetUserRegion::run();

        // Get all available venues in the region
        $query = Venue::available()
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
