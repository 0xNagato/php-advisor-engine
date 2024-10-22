<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CreateBooking;
use App\Actions\Region\GetUserRegion;
use App\Data\Booking\CreateBookingReturnData;
use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingCreateRequest;
use App\Http\Requests\Api\BookingUpdateRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\SendCustomerBookingPaymentForm;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Stripe\Exception\ApiErrorException;

class BookingController extends Controller
{
    public function store(BookingCreateRequest $request): JsonResponse|Response
    {
        $validatedData = $request->validated();

        /** @var Region $region */
        $region = GetUserRegion::run();

        try {
            /** @var CreateBookingReturnData $booking */
            $booking = CreateBooking::run(
                $validatedData['schedule_template_id'],
                $validatedData,
                $region->timezone,
                $region->currency
            );
        } catch (Exception) {
            return response()->json([
                'message' => 'Booking failed',
            ], 404);
        }

        $dayDisplay = $this->dayDisplay($region->timezone, $booking->booking->booking_at);

        $bookingResource = BookingResource::make($booking);

        $bookingResource = $bookingResource->additional([
            'region' => $region,
            'dayDisplay' => $dayDisplay,
        ]);

        return response()->json([
            'data' => $bookingResource,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function update(BookingUpdateRequest $request, Booking $booking): JsonResponse
    {
        $validatedData = $request->validated();

        if ($booking->status !== BookingStatus::PENDING) {
            return response()->json([
                'message' => 'Booking already confirmed or cancelled',
            ], 404);
        }

        if (! $booking->is_prime) {
            return $this->handleNonPrimeBooking($booking, $validatedData);
        }

        $booking->update([
            'guest_first_name' => $validatedData['first_name'],
            'guest_last_name' => $validatedData['last_name'],
            'guest_phone' => $validatedData['phone'],
            'guest_email' => $validatedData['email'],
            'notes' => $validatedData['notes'],
        ]);

        $booking->notify(new SendCustomerBookingPaymentForm(url: $validatedData['bookingUrl']));

        return response()->json([
            'message' => 'SMS Message Sent Successfully',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $booking = Booking::query()->findOrFail($id);

        if ($booking) {
            $booking->update(['status' => 'cancelled']);
            BookingCancelled::dispatch($booking);
        }

        return response()->json([
            'message' => 'Booking Cancelled',
        ]);
    }

    public function dayDisplay($timezone, $booking): string
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
     * @throws ApiErrorException
     */
    private function handleNonPrimeBooking(Booking $booking, array $validatedData): JsonResponse
    {
        app(BookingService::class)->processBooking($booking, $validatedData);

        $booking->update(['concierge_referral_type' => 'app']);

        return response()->json([
            'message' => 'Booking created successfully',
        ]);
    }
}
