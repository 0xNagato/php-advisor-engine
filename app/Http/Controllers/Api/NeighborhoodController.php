<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use Illuminate\Http\JsonResponse;

class NeighborhoodController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => Neighborhood::query()->pluck('name', 'id'),
        ]);
    }
}
