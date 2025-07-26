<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CheckCustomerHasConflictingNonPrimeBooking;
use App\Actions\Booking\CheckCustomerHasNonPrimeBooking;
use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\CreateStripePaymentIntent;
use App\Actions\Booking\UpdateBookingConciergeAttribution;
use App\Data\Booking\CreateBookingReturnData;
use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingCompleteRequest;
use App\Http\Requests\Api\BookingCreateRequest;
use App\Http\Requests\Api\BookingEmailInvoiceRequest;
use App\Http\Requests\Api\BookingUpdateRequest;
use App\Http\Resources\BookingResource;
use App\Mail\CustomerInvoice;
use App\Models\Booking;
use App\Models\Region;
use App\Models\Venue;
use App\Models\VipCode;
use App\Notifications\Booking\SendCustomerBookingPaymentForm;
use App\OpenApi\RequestBodies\BookingCompleteRequestBody;
use App\OpenApi\RequestBodies\BookingCreateRequestBody;
use App\OpenApi\RequestBodies\BookingEmailInvoiceRequestBody;
use App\OpenApi\Responses\BookingCompleteResponse;
use App\OpenApi\Responses\BookingEmailInvoiceResponse;
use App\OpenApi\Responses\BookingInvoiceStatusResponse;
use App\OpenApi\Responses\BookingResponse;
use App\OpenApi\Responses\MessageResponse;
use App\Services\BookingService;
use Exception;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
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
        security: 'BearerTokenSecurityScheme'
    )]
    #[RequestBody(factory: BookingCreateRequestBody::class)]
    #[OpenApiResponse(factory: BookingResponse::class)]
    public function store(BookingCreateRequest $request): JsonResponse|Response
    {
        $validatedData = $request->validated();

        // Look up VIP code if provided
        $vipCode = null;
        if (! empty($validatedData['vip_code'])) {
            $vipCode = VipCode::query()
                ->where('code', $validatedData['vip_code'])
                ->where('is_active', true)
                ->first();
        }

        // Get the venue from the schedule template ID
        $venue = Venue::query()
            ->whereHas('scheduleTemplates', function (Builder $query) use ($validatedData) {
                $query->where('id', $validatedData['schedule_template_id']);
            })
            ->first();

        if (! $venue || ! in_array($venue->status, [VenueStatus::ACTIVE, VenueStatus::HIDDEN])) {
            activity()
                ->withProperties([
                    'venue_id' => $venue?->id,
                    'venue_status' => $venue?->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking creation failed - Venue not accepting bookings');

            return response()->json([
                'message' => 'Venue is not currently accepting bookings',
            ], 422);
        }

        try {
            $device = isPrimaApp() ? 'mobile_app' : 'web';
            /** @var CreateBookingReturnData $booking */
            $booking = CreateBooking::run(
                $validatedData['schedule_template_id'],
                $validatedData,
                $vipCode,
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

        // Get the booking's venue region for proper tax info display
        $venueRegion = Region::query()->find($venue->region);
        $dayDisplay = $this->dayDisplay($venueRegion->timezone, $booking->booking->booking_at);

        $bookingResource = BookingResource::make($booking);

        $additionalData = [
            'region' => $venueRegion,
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
    //    #[OpenApi\Operation(
    //        tags: ['Bookings'],
    //        security: 'BearerTokenSecurityScheme'
    //    )]
    //    #[RequestBody(factory: BookingUpdateRequestBody::class)]
    //    #[OpenApiResponse(factory: MessageResponse::class)]
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

        if (! $booking->venue || ! in_array($booking->venue->status, [VenueStatus::ACTIVE, VenueStatus::HIDDEN])) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_status' => $booking->venue?->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking update failed - Venue not accepting bookings');

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
     * Complete a booking with payment (for prime) or direct confirmation (for non-prime)
     *
     * @throws ApiErrorException
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[RequestBody(factory: BookingCompleteRequestBody::class)]
    #[OpenApiResponse(factory: BookingCompleteResponse::class)]
    public function complete(BookingCompleteRequest $request, Booking $booking): JsonResponse
    {
        $validatedData = $request->validated();

        if ($booking->status !== BookingStatus::PENDING && $booking->status !== BookingStatus::GUEST_ON_PAGE) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'booking_status' => $booking->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking completion failed - Invalid status');

            return response()->json([
                'message' => 'Booking already confirmed or cancelled',
            ], 422);
        }

        if (! $booking->venue || ! in_array($booking->venue->status, [VenueStatus::ACTIVE, VenueStatus::HIDDEN])) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_status' => $booking->venue?->status,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Booking completion failed - Venue not active');

            return response()->json([
                'message' => 'Venue is not currently accepting bookings',
            ], 422);
        }

        if ($booking->is_prime) {
            // Prime booking - requires payment intent ID
            if (blank($validatedData['payment_intent_id'] ?? null)) {
                activity()
                    ->performedOn($booking)
                    ->withProperties([
                        'venue_id' => $booking->venue?->id,
                        'venue_name' => $booking->venue?->name,
                        'concierge_id' => auth()->user()?->concierge?->id,
                        'concierge_name' => auth()->user()?->name,
                    ])
                    ->log('API - Prime booking completion failed - Missing payment intent ID');

                return response()->json([
                    'message' => 'Payment intent ID is required for prime bookings',
                ], 422);
            }

            try {
                $result = CompleteBooking::run(
                    $booking,
                    $validatedData['payment_intent_id'] ?? null,
                    [
                        'firstName' => $validatedData['first_name'],
                        'lastName' => $validatedData['last_name'],
                        'phone' => $validatedData['phone'],
                        'email' => $validatedData['email'] ?? null,
                        'notes' => $validatedData['notes'] ?? '',
                        'r' => $validatedData['r'] ?? '',
                    ]
                );

                activity()
                    ->performedOn($booking)
                    ->withProperties([
                        'venue_id' => $booking->venue?->id,
                        'venue_name' => $booking->venue?->name,
                        'payment_intent_id' => $validatedData['payment_intent_id'] ?? null,
                        'concierge_id' => auth()->user()?->concierge?->id,
                        'concierge_name' => auth()->user()?->name,
                    ])
                    ->log('API - Prime booking completed successfully');

                $booking->refresh();

                // Load the necessary relationships
                $booking->load(['venue', 'venue.inRegion']);

                /** @var Region $region */
                $region = Region::query()->find($booking->venue->region);
                $dayDisplay = $this->dayDisplay($region->timezone, $booking->booking_at);

                $bookingResource = BookingResource::make($booking)->additional([
                    'region' => $region,
                    'dayDisplay' => $dayDisplay,
                ]);

                $responseData = [
                    'booking' => $bookingResource,
                    'result' => $result,
                ];

                // Only include the invoice URL if the invoice has been processed and uploaded
                if ($booking->invoice_path) {
                    $responseData['invoice_download_url'] = route('customer.invoice.download',
                        ['uuid' => $booking->uuid]);
                } else {
                    $responseData['invoice_status'] = 'processing';
                    $responseData['invoice_message'] = 'Invoice is being generated and will be available shortly. You can check back or it will be emailed once ready.';
                }

                return response()->json([
                    'message' => 'Booking completed successfully',
                    'data' => $responseData,
                ]);

            } catch (Exception $e) {
                activity()
                    ->performedOn($booking)
                    ->withProperties([
                        'venue_id' => $booking->venue?->id,
                        'venue_name' => $booking->venue?->name,
                        'payment_intent_id' => $validatedData['payment_intent_id'] ?? null,
                        'error' => $e->getMessage(),
                        'concierge_id' => auth()->user()?->concierge?->id,
                        'concierge_name' => auth()->user()?->name,
                    ])
                    ->log('API - Prime booking completion failed - Exception');

                return response()->json([
                    'message' => 'Booking completion failed: '.$e->getMessage(),
                ], 500);
            }
        } else {
            // Non-prime booking - check for existing booking
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
                    ->log('API - Non-prime booking completion failed - Customer already has booking for this day');

                return response()->json([
                    'message' => 'Customer already has a non-prime booking for this day',
                ], 422);
            }

            // Check for conflicting non-prime booking within time window
            $hasConflictingBooking = CheckCustomerHasConflictingNonPrimeBooking::run(
                $validatedData['phone'],
                $booking->booking_at
            );

            if ($hasConflictingBooking) {
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
                    ->log('API - Non-prime booking completion failed - Customer has conflicting booking within '.CheckCustomerHasConflictingNonPrimeBooking::BOOKING_WINDOW_HOURS.'-hour window');

                $conflictDate = $hasConflictingBooking->booking_at->format('F j');
                $conflictTime = $hasConflictingBooking->booking_at->format('g:i A');

                return response()->json([
                    'message' => "You already have a no‑fee booking on {$conflictDate} at {$conflictTime}. Bookings must be at least 2 hours apart. Please select a different time.",
                ], 422);
            }

            // Complete non-prime booking
            $formData = [
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'phone' => $validatedData['phone'],
                'email' => $validatedData['email'] ?? null,
                'notes' => $validatedData['notes'] ?? '',
            ];

            app(BookingService::class)->processBooking($booking, $formData);

            // Apply customer attribution logic for returning customers
            UpdateBookingConciergeAttribution::run($booking, $validatedData['phone']);

            $booking->update(['concierge_referral_type' => 'app']);

            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_name' => $booking->venue?->name,
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Non-prime booking completed successfully');

            $booking->refresh();

            // Load the necessary relationships
            $booking->load(['venue', 'venue.inRegion']);

            /** @var Region $region */
            $region = Region::query()->find($booking->venue->region);
            $dayDisplay = $this->dayDisplay($region->timezone, $booking->booking_at);

            $bookingResource = BookingResource::make($booking)->additional([
                'region' => $region,
                'dayDisplay' => $dayDisplay,
            ]);

            $responseData = [
                'booking' => $bookingResource,
            ];

            // Include invoice status for all confirmed bookings
            if ($booking->invoice_path) {
                $responseData['invoice_download_url'] = route('customer.invoice.download', ['uuid' => $booking->uuid]);
            } else {
                $responseData['invoice_status'] = 'processing';
                $responseData['invoice_message'] = 'Invoice is being generated and will be available shortly. You can check back or it will be emailed once ready.';
            }

            return response()->json([
                'message' => 'Booking completed successfully',
                'data' => $responseData,
            ]);
        }
    }

    /**
     * Delete (abandon) a booking by ID.
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
        security: 'BearerTokenSecurityScheme'
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
     * Get invoice status and download URL for a booking
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[OpenApiResponse(factory: BookingInvoiceStatusResponse::class)]
    public function invoiceStatus(Booking $booking): JsonResponse
    {
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return response()->json([
                'message' => 'Invoice not available for this booking',
            ], 422);
        }

        if ($booking->invoice_path) {
            return response()->json([
                'status' => 'ready',
                'invoice_download_url' => route('customer.invoice.download', ['uuid' => $booking->uuid]),
                'message' => 'Invoice is ready for download',
            ]);
        }

        return response()->json([
            'status' => 'processing',
            'message' => 'Invoice is being generated and will be available shortly',
        ]);
    }

    /**
     * Email invoice to a customer
     */
    #[OpenApi\Operation(
        tags: ['Bookings'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[RequestBody(factory: BookingEmailInvoiceRequestBody::class)]
    #[OpenApiResponse(factory: MessageResponse::class, statusCode: 422)]
    #[OpenApiResponse(factory: BookingEmailInvoiceResponse::class, statusCode: 200)]
    public function emailInvoice(BookingEmailInvoiceRequest $request, Booking $booking): JsonResponse
    {
        $validatedData = $request->validated();

        if ($booking->status !== BookingStatus::CONFIRMED) {
            return response()->json([
                'message' => 'Invoice can only be emailed for confirmed bookings',
            ], 422);
        }

        if (! $booking->invoice_path) {
            return response()->json([
                'message' => 'Invoice is not yet available. Please try again shortly.',
            ], 422);
        }

        // Use provided email or fall back to booking's guest email
        $emailAddress = $validatedData['email'] ?? $booking->guest_email;

        if (blank($emailAddress)) {
            return response()->json([
                'message' => 'No email address provided and no email address available for this booking',
            ], 422);
        }

        try {
            $mailable = new CustomerInvoice($booking);
            $mailable->attachFromStorageDisk('do', $booking->invoice_path)
                ->from('welcome@primavip.co', 'PRIMA');

            Mail::to($emailAddress)->send($mailable);

            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_name' => $booking->venue?->name,
                    'email_sent_to' => $emailAddress,
                    'email_provided' => isset($validatedData['email']),
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Invoice emailed successfully');

            return response()->json([
                'message' => 'Invoice sent to '.$emailAddress,
                'data' => [
                    'email' => $emailAddress,
                ],
            ]);

        } catch (Exception $e) {
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'venue_id' => $booking->venue?->id,
                    'venue_name' => $booking->venue?->name,
                    'email_sent_to' => $emailAddress,
                    'error' => $e->getMessage(),
                    'concierge_id' => auth()->user()?->concierge?->id,
                    'concierge_name' => auth()->user()?->name,
                ])
                ->log('API - Invoice email failed');

            return response()->json([
                'message' => 'Failed to send invoice email. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate a human-readable display of the booking's date and time.
     */
    private function dayDisplay(string $timezone, mixed $booking): string
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

        // Check for conflicting non-prime booking within a 2-hour window
        $hasConflictingBooking = CheckCustomerHasConflictingNonPrimeBooking::run(
            $validatedData['phone'],
            $booking->booking_at
        );

        if ($hasConflictingBooking) {
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
                ->log('API - Non-prime booking update failed - Customer has conflicting booking within '.CheckCustomerHasConflictingNonPrimeBooking::BOOKING_WINDOW_HOURS.'-hour window');

            $conflictDate = $hasConflictingBooking->booking_at->format('F j');
            $conflictTime = $hasConflictingBooking->booking_at->format('g:i A');

            return response()->json([
                'message' => "You already have a no‑fee booking on {$conflictDate} at {$conflictTime}. Bookings must be at least 2 hours apart. Please select a different time.",
            ], 422);
        }

        app(BookingService::class)->processBooking($booking, $validatedData);

        // Apply customer attribution logic for returning customers
        UpdateBookingConciergeAttribution::run($booking, $validatedData['phone']);

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
