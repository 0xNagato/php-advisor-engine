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
        $venues = Venue::available()
            ->where('region', $region->id)
            ->where('status', VenueStatus::ACTIVE)
            ->get(['id', 'name']);

        return response()->json([
            'data' => $venues,
        ]);
    }
}
