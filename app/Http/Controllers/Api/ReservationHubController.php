<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HubRequest;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationHubController extends Controller
{
    public function __invoke(HubRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $reservation = new ReservationService(
            date: $validatedData['date'],
            guestCount: $validatedData['guest_count'],
            reservationTime: $validatedData['reservation_time'],
        );

        return response()->json([
            'data' => [
                $reservation->getVenueSchedules($validatedData['venue_id']),
            ],
        ]);
    }
}
