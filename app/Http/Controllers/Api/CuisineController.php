<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuisine;
use Illuminate\Http\JsonResponse;

class CuisineController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => Cuisine::query()->pluck('name', 'id'),
        ]);
    }
}
