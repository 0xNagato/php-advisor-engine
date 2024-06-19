<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UpdateRegionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'region' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    Region::query()->find($value) ?: $fail("The {$attribute} is invalid.");
                },
            ],
        ]);

        session(['region' => $validated['region']]);

        return response()->noContent();
    }
}
