<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CreateBooking;
use App\Actions\Region\GetUserRegion;
use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingCreateRequest;
use App\Http\Requests\Api\BookingUpdateRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Notifications\Booking\ConfirmReservation;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    public function store(BookingCreateRequest $request): JsonResponse|Response
    {
        $validatedData = $request->validated();

        $region = GetUserRegion::run();

        try {
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

        // Add additional data to the resource response
        $bookingResource = $bookingResource->additional([
            'region' => $region,
            'dayDisplay' => $dayDisplay,
        ]);

        return response()->json([
            'data' => $bookingResource,
        ]);
    }

    public function update(BookingUpdateRequest $request, Booking $booking): JsonResponse
    {
        $validatedData = $request->validated();

        if ($booking->status !== BookingStatus::PENDING) {
            return response()->json([
                'message' => 'Booking already confirmed or cancelled',
            ], 404);
        }

        $booking->update([
            'guest_first_name' => $validatedData['first_name'],
            'guest_last_name' => $validatedData['last_name'],
            'guest_phone' => $validatedData['phone'],
            'guest_email' => $validatedData['email'],
            'notes' => $validatedData['notes'],
        ]);

        $booking->notify(new ConfirmReservation(url: $validatedData['bookingUrl']));

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
        $bookingDate = $booking->setTimezone($timezone)->startOfDay();
        $today = now($timezone)->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $time = $booking->format('g:i a');

        logger()->info('BookingController Day Display Debug', [
            'booking_date' => $bookingDate->toDateTimeString(),
            'today' => $today->toDateTimeString(),
            'tomorrow' => $tomorrow->toDateTimeString(),
            'timezone' => $timezone,
        ]);

        if ($bookingDate->equalTo($today)) {
            return 'Today at '.$time;
        }

        if ($bookingDate->equalTo($tomorrow)) {
            return 'Tomorrow at '.$time;
        }

        return $bookingDate->format('D, M j').' at '.$time;
    }
}
