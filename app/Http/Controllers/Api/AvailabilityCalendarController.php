<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CalendarRequest;
use App\Http\Resources\VenueResource;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class AvailabilityCalendarController extends Controller
{
    public function __invoke(CalendarRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $reservation = new ReservationService(
            date: $validatedData['date'],
            guestCount: $validatedData['guest_count'],
            reservationTime: $validatedData['reservation_time'],
            timeslotCount: $validatedData['timeslot_count'] ?? 5 // Default to 5 if not provided
        );

        return response()->json([
            'data' => [
                'venues' => VenueResource::collection($reservation->getAvailableVenues()),
                'timeslots' => $reservation->getTimeslotHeaders(),
            ],
        ]);
    }
}
