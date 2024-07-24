<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Traits\ManagesBookingForms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimeslotController extends Controller
{
    use ManagesBookingForms;

    public function __invoke(Request $request, $region = 'miami'): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required','date'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();

        // Get the validated date from the request
        $date = $validated['date'];

        // Get the region from the session or default to Miami
        $region = Region::query()->find($region);

        // Set the timezone from the region
        $this->timezone = $region->timezone;

        $reservationTimeOptions = $this->getReservationTimeOptions($date);

        return response()->json([
            'data' => [
                'timeslots' => $reservationTimeOptions,
            ],
        ]);
    }
}
