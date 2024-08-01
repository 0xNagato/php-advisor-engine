<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reservations\GetReservationTimeOptions;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimeslotController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Validate the requested date
        $validatedDate = $this->validateDate($request);

        if ($validatedDate instanceof JsonResponse) {
            return $validatedDate;
        }

        return response()->json([
            'data' => GetReservationTimeOptions::run(date: $validatedDate),
        ]);
    }

    private function validateDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $validator->validated()['date'];
    }
}
