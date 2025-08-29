<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CalendarRequest;
use App\Http\Resources\VenueResource;
use App\Models\Region;
use App\Models\VipSession;
use App\OpenApi\Parameters\CalendarParameter;
use App\OpenApi\Responses\CalendarResponse;
use App\Services\ReservationService;
use App\Services\VipCodeService;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class AvailabilityCalendarController extends Controller
{
    public function __construct(
        private readonly VipCodeService $vipCodeService
    ) {}

    /**
     * Fetch availability data for the venues and timeslots.
     */
    #[OpenApi\Operation(
        tags: ['Availability Calendars'],
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

        // Detect VIP context from the authenticated user's token
        $vipContext = $this->detectVipContext($request);

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
            vipContext: $vipContext, // Pass VIP context for venue collection filtering
            userLatitude: $validatedData['user_latitude'] ?? null,
            userLongitude: $validatedData['user_longitude'] ?? null,
        );

        return response()->json([
            'data' => [
                'venues' => VenueResource::collection($reservation->getAvailableVenues()),
                'timeslots' => $reservation->getTimeslotHeaders(),
                'venue_collection' => $vipContext ? $this->prepareVenueCollectionForApi($vipContext) : null,
            ],
        ]);
    }

    /**
     * Detect VIP context from the VIP session token
     */
    private function detectVipContext($request): ?array
    {
        $authHeader = $request->header('Authorization');
        if (! $authHeader || ! str_starts_with((string) $authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr((string) $authHeader, 7); // Remove 'Bearer ' prefix

        // Find VIP session for this token
        $vipSession = VipSession::with(['vipCode.concierge', 'vipCode.venueCollections.items.venue'])
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $vipSession) {
            return null;
        }

        return [
            'vip_code' => $vipSession->vipCode,
            'concierge' => $vipSession->vipCode->concierge,
            'session' => $vipSession,
        ];
    }

    /**
     * Prepare venue collection data for API response
     */
    private function prepareVenueCollectionForApi(array $vipContext): ?array
    {
        $vipCode = $vipContext['vip_code'];
        $concierge = $vipContext['concierge'];

        // Check for VIP code-level collection first (overrides concierge-level)
        $collection = $vipCode->venueCollections()->with(['items.venue', 'items' => function ($query) {
            $query->active()->ordered();
        }])->first();

        if (! $collection) {
            // Fall back to concierge-level collection
            $collection = $concierge->venueCollections()->with(['items.venue', 'items' => function ($query) {
                $query->active()->ordered();
            }])->first();
        }

        if (! $collection || ! $collection->is_active) {
            return null;
        }

        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'is_active' => $collection->is_active,
            'source' => $collection->vip_code_id ? 'vip_code' : 'concierge',
            'items_count' => $collection->items->count(),
        ];
    }
}
