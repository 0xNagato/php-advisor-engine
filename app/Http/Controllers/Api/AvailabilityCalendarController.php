<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AvailabilityCalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validatedData = $this->validateReservationData($request);

        if ($validatedData instanceof JsonResponse) {
            return $validatedData;
        }

        $reservation = new ReservationService(
            date: $validatedData['date'],
            guestCount: $validatedData['guest_count'],
            reservationTime: $validatedData['reservation_time'],
        );

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($reservation->getAvailableRestaurants()),
                'timeslots' => $reservation->getTimeslotHeaders(),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function validateReservationData(Request $request): JsonResponse|array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'guest_count' => ['required', 'integer', 'min:2'],
            'reservation_time' => ['required', 'date_format:H:i:s'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $validator->validated();
    }
}
