<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CheckCustomerHasNonPrimeBooking;
use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\CreateStripePaymentIntent;
use App\Actions\Region\GetUserRegion;
use App\Data\Booking\CreateBookingReturnData;
use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingCreateRequest;
use App\Http\Requests\Api\BookingUpdateRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use App\Models\Venue;
use App\Notifications\Booking\SendCustomerBookingPaymentForm;
use App\OpenApi\RequestBodies\BookingCreateRequestBody;
use App\OpenApi\RequestBodies\BookingUpdateRequestBody;
use App\OpenApi\Responses\BookingResponse;
use App\OpenApi\Responses\MessageResponse;
use App\Services\BookingService;
use Exception;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Stripe\Exception\ApiErrorException;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\RequestBody;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class BookingController extends Controller
{
    /**
     * Create a new booking.
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
    )]
    #[RequestBody(factory: BookingCreateRequestBody::class)]
    #[OpenApiResponse(factory: BookingResponse::class)]
    public function store(BookingCreateRequest $request): JsonResponse|Response
    {
        $validatedData = $request->validated();

        // Get the venue from the schedule template ID
        $venue = Venue::query()
            ->whereHas('scheduleTemplates', function (Builder $query) use ($validatedData) {
                $query->where('id', $validatedData['schedule_template_id']);
            })
            ->first();

        if (! $venue || $venue->status !== VenueStatus::ACTIVE) {
            activity()
                ->withProperties([
                    'venue_id' => $venue?->id,
                    'venue_status' => $venue?->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking creation failed - Venue not active');

            return response()->json([
                'message' => 'Venue is not currently accepting bookings',
            ], 422);
        }

        /** @var Region $region */
        $region = GetUserRegion::run();

        try {
            $device = isPrimaApp() ? 'mobile_app' : 'web';
            /** @var CreateBookingReturnData $booking */
            $booking = CreateBooking::run(
                $validatedData['schedule_template_id'],
                $validatedData,
                null,
                'api',
                $device
            );
        } catch (Exception $e) {
            activity()
                ->withProperties([
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                    'error' => $e->getMessage(),
                ])
                ->log('API - Booking creation failed - Exception');

            return response()->json([
                'message' => 'Booking failed',
            ], 404);
        }

        $dayDisplay = $this->dayDisplay($region->timezone, $booking->booking->booking_at);

        $bookingResource = BookingResource::make($booking);

        $additionalData = [
            'region' => $region,
            'dayDisplay' => $dayDisplay,
        ];

        // Create payment intent for prime bookings
        if ($booking->booking->is_prime) {
            try {
                $paymentIntentSecret = CreateStripePaymentIntent::run($booking->booking);
                $additionalData['paymentIntentSecret'] = $paymentIntentSecret;
            } catch (ApiErrorException $e) {
                activity()
                    ->performedOn($booking->booking)
                    ->withProperties([
                        'venue_id' => $venue->id,
                        'venue_name' => $venue->name,
                        'concierge_id' => auth()->user()?->concierge?->id,
                        'concierge_name' => auth()->user()?->name,
                        'error' => $e->getMessage(),
                    ])
                    ->log('API - Payment intent creation failed');

                return response()->json([
                    'message' => 'Payment processing unavailable. Please try again.',
                ], 500);
            }
        }

        $bookingResource = $bookingResource->additional($additionalData);

        return response()->json([
            'data' => $bookingResource,
        ]);
    }

    /**
     * Update an existing booking.
     *
     * @throws ApiErrorException
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
    )]
    #[RequestBody(factory: BookingUpdateRequestBody::class)]
    #[OpenApiResponse(factory: MessageResponse::class)]
    public function update(BookingUpdateRequest $request, Booking $booking): JsonResponse
    {
        $validatedData = $request->validated();

        if ($booking->status !== BookingStatus::PENDING) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'booking_status' => $booking->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking update failed - Invalid status');

            return response()->json([
                'message' => 'Booking already confirmed or cancelled',
            ], 404);
        }

        if (! $booking->venue || $booking->venue->status !== VenueStatus::ACTIVE) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_status' => $booking->venue?->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking update failed - Venue not active');

            return response()->json([
                'message' => 'Venue is not currently accepting bookings',
            ], 422);
        }

        if (! $booking->is_prime) {
            return $this->handleNonPrimeBooking($booking, $validatedData);
        }

        $booking->update([
            'guest_first_name' => $validatedData['first_name'],
            'guest_last_name' => $validatedData['last_name'],
            'guest_phone' => $validatedData['phone'],
            'guest_email' => $validatedData['email'] ?? null,
            'notes' => $validatedData['notes'],
        ]);

        activity()
            ->performedOn($booking)
            ->withProperties([
                'venue_id' => $booking->venue?->id,
                'venue_name' => $booking->venue?->name,
                'concierge_id' => auth()->user()?->concierge?->id,
                'concierge_name' => auth()->user()?->name,
            ])
            ->log('API - Booking updated successfully');

        $booking->notify(new SendCustomerBookingPaymentForm(url: $validatedData['bookingUrl']));

        return response()->json([
            'message' => 'SMS Message Sent Successfully',
        ]);
    }

    /**
     * Delete (abandon) a booking by ID.
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
    )]
    #[OpenApiResponse(factory: MessageResponse::class)]
    public function destroy(int $id): JsonResponse
    {
        /** @var Booking $booking */
        $booking = Booking::query()->findOrFail($id);

        if (! in_array($booking->status, [BookingStatus::PENDING, BookingStatus::GUEST_ON_PAGE])) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'booking_id' => $booking->id,
                    'current_status' => $booking->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking abandon failed - Invalid status');

            return response()->json([
                'message' => 'Booking cannot be abandoned in its current status',
            ]);
        }

        $booking->update(['status' => BookingStatus::ABANDONED]);

        activity()
            ->performedOn($booking)
            ->withProperties([
                'booking_id' => $booking->id,
                'previous_status' => $booking->getOriginal('status'),
                'concierge_id' => auth()->user()?->concierge?->id,
                'concierge_name' => auth()->user()?->name,
            ])
            ->log('API - Booking abandoned successfully');

        return response()->json([
            'message' => 'Booking Abandoned',
        ]);
    }

    /**
     * Generate a human-readable display of the booking's date and time.
     */
    public function dayDisplay(string $timezone, mixed $booking): string
    {
        $time = $booking->format('g:i a');
        $bookingDate = $booking->startOfDay();
        $today = today();
        $tomorrow = now($timezone)->addDay()->startOfDay();

        if ($bookingDate->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Today at '.$time;
        }

        if ($bookingDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
            return 'Tomorrow at '.$time;
        }

        return $bookingDate->format('D, M j').' at '.$time;
    }

    /**
     * Handle updates for non-prime bookings.
     *
     * @throws ApiErrorException
     */
    private function handleNonPrimeBooking(Booking $booking, array $validatedData): JsonResponse
    {
        // Check if the customer already has a non-prime booking for this day
        $hasExistingBooking = CheckCustomerHasNonPrimeBooking::run(
            $validatedData['phone'],
            $booking->booking_at->format('Y-m-d'),
            $booking->venue->timezone
        );

        if ($hasExistingBooking) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_name' => $booking->venue?->name,
                    'guest_phone' => $validatedData['phone'],
                    'booking_date' => $booking->booking_at->format('Y-m-d'),
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Non-prime booking update failed - Customer already has booking for this day');

            return response()->json([
                'message' => 'Customer already has a non-prime booking for this day',
            ], 422);
        }

        app(BookingService::class)->processBooking($booking, $validatedData);

        $booking->update(['concierge_referral_type' => 'app']);

        activity()
            ->performedOn($booking)
            ->withProperties([
                'venue_id' => $booking->venue?->id,
                'venue_name' => $booking->venue?->name,
                'concierge_id' => auth()->user()?->concierge?->id,
                'concierge_name' => auth()->user()?->name,
            ])
            ->log('API - Non-prime booking created successfully');

        return response()->json([
            'message' => 'Booking created successfully',
        ]);
    }
}
