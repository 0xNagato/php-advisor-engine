<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use App\OpenApi\Parameters\RegionParameter;
use App\OpenApi\Responses\NeighborhoodListResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class NeighborhoodController extends Controller
{
    /**
     * Retrieve a list of active neighborhoods.
     *
     * This endpoint fetches a collection of active neighborhoods
     * with their respective IDs and names.
     *
     * @return JsonResponse A JSON response containing the neighborhoods.
     */
    #[OpenApi\Operation(
        tags: ['Neighborhoods'],
    )]
    #[Parameters(factory: RegionParameter::class)]
    #[OpenApiResponse(factory: NeighborhoodListResponse::class)]
    public function __invoke(Request $request): JsonResponse
    {
        $data = Neighborhood::query()
            ->when($request->has('region'), function ($query) use ($request) {
                $query->where('region', $request->region);
            })
            ->pluck('name', 'id');

        return response()->json([
            'data' => $data,
        ]);
    }
}
