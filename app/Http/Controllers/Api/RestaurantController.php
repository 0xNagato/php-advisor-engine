<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class RestaurantController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $region = GetUserRegion::run();

        // Get all available restaurants in the region
        $restaurants = Restaurant::available()
            ->where('region', $region->id)
            ->get(['id', 'restaurant_name']);

        return response()->json([
            'data' => $restaurants,
        ]);
    }
}
