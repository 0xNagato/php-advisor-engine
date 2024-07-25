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
        $region = Region::query()->find($region);

        if (!$region) {
            return response()->json(['error' => 'Region not found'], 404);
        }

        // Validate the requested date
        $validatedDate = $this->validateDate($request);
        if ($validatedDate instanceof JsonResponse) {
            return $validatedDate;
        }

        // Set the timezone from the region
        $this->timezone = $region->timezone;

        $reservationTimeOptions = $this->getReservationTimeOptions($validatedDate);

        return response()->json([
            'data' => [
                'timeslots' => $reservationTimeOptions,
            ],
        ]);
    }

    private function validateDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required','date'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $validator->validated()['date'];
    }
}
