<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
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
