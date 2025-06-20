<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NeighborhoodController extends Controller
{
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
