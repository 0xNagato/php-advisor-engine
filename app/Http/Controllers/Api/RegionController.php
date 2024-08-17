<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegionRequest;
use App\Models\Region;
use App\Traits\ManagesBookingForms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegionController extends Controller
{
    use ManagesBookingForms;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Region::active()->pluck('name', 'id'),
        ]);
    }

    public function store(RegionRequest $request): JsonResponse|Response
    {
        $request->user()->update([
            'region' => $request->validated()['region'],
        ]);

        return response()->noContent();
    }
}
