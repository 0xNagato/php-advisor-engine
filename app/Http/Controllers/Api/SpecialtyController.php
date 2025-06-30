<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\OpenApi\Parameters\RegionParameter;
use App\OpenApi\Responses\SpecialtyListResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class SpecialtyController extends Controller
{
    /**
     * Retrieve a list of active cuisines.
     *
     * This endpoint fetches a collection of active cuisines
     * with their respective IDs and names.
     *
     * @return JsonResponse A JSON response containing the cuisines.
     */
    #[OpenApi\Operation(
        tags: ['Specialties'],
    )]
    #[Parameters(factory: RegionParameter::class)]
    #[OpenApiResponse(factory: SpecialtyListResponse::class)]
    public function __invoke(Request $request): JsonResponse
    {
        $data = when(
            $request->has('region'),
            fn () => Specialty::getSpecialtiesByRegion($request->region),
            fn () => Specialty::query()->pluck('name', 'id')
        );

        return response()->json([
            'data' => $data,
        ]);
    }
}
