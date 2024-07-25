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
        $regions = Region::active()->get()->transform(fn($region) => [
            'id' => $region->id,
            'name' => $region->name,
        ]);

        return response()->json([
            'data' => [
                'regions' => $regions,
            ],
        ]);
    }
}
