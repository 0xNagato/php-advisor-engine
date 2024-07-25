<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvailableRestaurantController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'guest_count' => ['required','integer','min:2'],
            'reservation_time' => ['required','date_format:H:i:s'],
        ]);

        if (!$region) {
            return response()->json(['error' => 'Region not found'], 404);
        }

        $validated = $validator->validated();

        $reservation = new ReservationService(
            date: $validated['date'],
            guestCount: $validated['guest_count'],
            reservationTime: $validated['reservation_time'],
        );

        return response()->json([
            'data' => [
                'available_restaurants' => $reservation->getAvailableRestaurants()->toArray(),
                'timeslot_headers' => $reservation->getTimeslotHeaders(),
            ]
        ]);
    }
}
