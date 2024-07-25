<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class AvailableRestaurantController extends Controller
{
    public function __invoke($region = 'miami'): JsonResponse
    {
        $region = Region::query()->find($region);

        if (!$region) {
            return response()->json(['error' => 'Region not found'], 404);
        }

        // Get all available restaurants in the region
        $restaurants = Restaurant::available()
            ->where('region', $region->id)
            ->get(['id', 'restaurant_name']);

        return response()->json([
            'data' => [
                'restaurants' => $restaurants,
            ],
        ]);
    }
}
