<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuisine;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CuisineController extends Controller
{
    /**
     * Retrieve a list of active cuisines.
     *
     * This endpoint fetches a collection of active cuisines
     * with their respective IDs and names.
     *
     * @return JsonResponse A JSON response containing the cuisines.
     */
    #[OpenApi\Operation]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => Cuisine::query()->pluck('name', 'id'),
        ]);
    }
}
