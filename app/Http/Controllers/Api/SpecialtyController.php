<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;

class SpecialtyController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => Specialty::query()->pluck('name', 'id'),
        ]);
    }
}
