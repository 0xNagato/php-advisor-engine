<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Traits\ManagesBookingForms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeslotController extends Controller
{
    use ManagesBookingForms;

    public function __invoke(Request $request): JsonResponse
    {
        // Get the region from the session or default to Miami
        $region = Region::query()->find(session('region', 'miami'));

        // Set the timezone from the region
        $this->timezone = $region->timezone;

        // Get the date from the request or default to today
        $date = $request->input('date', now($this->timezone)->format('Y-m-d'));

        $reservationTimeOptions = $this->getReservationTimeOptions($date);

        return response()->json([
            'timeslots' => $reservationTimeOptions,
        ]);
    }
}
