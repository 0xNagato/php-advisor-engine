<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $region = GetUserRegion::run();

        // Get all available venues in the region
        $venues = Venue::available()
            ->where('region', $region->id)
            ->get(['id', 'name']);

        return response()->json([
            'data' => $venues,
        ]);
    }
}
