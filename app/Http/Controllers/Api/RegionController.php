<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Traits\ManagesBookingForms;
use Illuminate\Http\JsonResponse;

class RegionController extends Controller
{
    use ManagesBookingForms;

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => Region::active()->pluck('name', 'id'),
        ]);
    }
}
