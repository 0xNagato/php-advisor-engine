<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CalendarRequest;
use App\Http\Resources\VenueResource;
use App\Models\Region;
use App\OpenApi\Parameters\CalendarParameter;
use App\OpenApi\Responses\CalendarResponse;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class AvailabilityCalendarController extends Controller
{
    /**
     * Fetch availability data for the venues and timeslots.
     */
    #[OpenApi\Operation(
        tags: ['Availability Calendars'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[Parameters(factory: CalendarParameter::class)]
    #[OpenApiResponse(factory: CalendarResponse::class)]
    public function __invoke(CalendarRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // Get the region - either from the request parameter or fallback to null (which will use the default)
        $region = null;
        if (isset($validatedData['region'])) {
            $region = Region::query()->where('id', $validatedData['region'])->first();
        }

        $reservation = new ReservationService(
            date: $validatedData['date'],
            guestCount: $validatedData['guest_count'],
            reservationTime: $validatedData['reservation_time'],
            timeslotCount: $validatedData['timeslot_count'] ?? 5, // Default to 5 if not provided
            timeSlotOffset: $validatedData['time_slot_offset'] ?? 1, // Default to 1 if not provided
            cuisines: $validatedData['cuisine'] ?? [],
            neighborhood: $validatedData['neighborhood'] ?? null,
            region: $region, // Pass the region to override the default
            specialty: $validatedData['specialty'] ?? [],
        );

        return response()->json([
            'request' => $request->all(),
            'data' => [
                'venues' => VenueResource::collection($reservation->getAvailableVenues()),
                'timeslots' => $reservation->getTimeslotHeaders(),
            ],
        ]);
    }
}
