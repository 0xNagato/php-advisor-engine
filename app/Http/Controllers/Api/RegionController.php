<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Rules\ActiveRegion;
use App\Traits\ManagesBookingForms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegionController extends Controller
{
    use ManagesBookingForms;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Region::active()->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): JsonResponse|Response
    {
        $validatedData = $this->validateRegionData($request);

        if ($validatedData instanceof JsonResponse) {
            return $validatedData;
        }

        $request->user()->update([
            'region' => $validatedData['region'],
        ]);

        return response()->noContent();
    }

    /**
     * @throws ValidationException
     */
    private function validateRegionData(Request $request): JsonResponse|array
    {
        $validator = Validator::make($request->all(), [
            'region' => ['required', new ActiveRegion],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $validator->validated();
    }
}
