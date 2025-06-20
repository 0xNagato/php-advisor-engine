<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegionRequest;
use App\Models\Region;
use App\Traits\ManagesBookingForms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class RegionController extends Controller
{
    use ManagesBookingForms;

    /**
     * Retrieve a list of active regions.
     *
     * This endpoint fetches a collection of active regions
     * with their respective IDs and names.
     *
     * @return JsonResponse A JSON response containing the regions.
     */
    #[OpenApi\Operation]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Region::active()->pluck('name', 'id'),
        ]);
    }

    /**
     * Update the region for the authenticated user.
     *
     * This endpoint allows authenticated users to update
     * their preferred region settings.
     *
     * @param  RegionRequest  $request  The validated request containing the region data.
     * @return JsonResponse|Response An empty JSON response indicating success.
     */
    #[OpenApi\Operation]
    public function store(RegionRequest $request): JsonResponse|Response
    {
        $request->user()->update([
            'region' => $request->validated()['region'],
        ]);

        return response()->noContent();
    }
}
