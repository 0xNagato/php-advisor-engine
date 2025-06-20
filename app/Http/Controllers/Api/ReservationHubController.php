<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HubRequest;
use App\OpenApi\Parameters\HubParameter;
use App\OpenApi\Responses\HubResponse;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class ReservationHubController extends Controller
{
    /**
     * Fetch venue schedules for a specific venue based on reservation criteria.
     */
    #[OpenApi\Operation(
        tags: ['Reservation Hubs'],
    )]
    #[Parameters(factory: HubParameter::class)]
    #[OpenApiResponse(factory: HubResponse::class)]
    public function __invoke(HubRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $reservation = new ReservationService(
            date: $validatedData['date'],
            guestCount: $validatedData['guest_count'],
            reservationTime: $validatedData['reservation_time'],
            timeslotCount: $validatedData['timeslot_count'] ?? 5,
            timeSlotOffset: $validatedData['time_slot_offset'] ?? 1
        );

        return response()->json([
            'data' => [
                $reservation->getVenueSchedules($validatedData['venue_id']),
            ],
        ]);
    }
}
