<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Booking $booking
 * @property string $bookingUrl
 * @property string $bookingVipUrl
 * @property string $qrCode
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both CreateBookingReturnData objects and direct Booking models
        $booking = $this->booking ?? $this->resource;

        $data = [
            'bookings_enabled' => config('app.bookings_enabled'),
            'bookings_disabled_message' => config('app.bookings_disabled_message'),
            'id' => $booking->id,
            'guest_count' => $booking->guest_count,
            'dayDisplay' => $this->additional['dayDisplay'] ?? null,
            'status' => $booking->status,
            'venue' => $booking->venue->name,
            'logo' => $booking->venue->logo,
            'total' => money($booking->total_with_tax_in_cents, $booking->currency)->format(),
            'subtotal' => money($booking->total_fee, $booking->currency)->format(),
            'tax_rate_term' => $booking->tax > 0 ? $this->additional['region']->tax_rate_term : null,
            'tax_amount' => $booking->tax > 0 ? money($booking->tax_amount_in_cents, $booking->currency) : null,
            'bookingUrl' => $this->bookingUrl ?? '#',
            'qrCode' => $this->qrCode ?? '',
            'is_prime' => $booking->is_prime ? 'true' : 0,
            'booking_at' => $booking->booking_at,
        ];

        // Include payment intent secret for prime bookings
        if (isset($this->additional['paymentIntentSecret'])) {
            $data['paymentIntentSecret'] = $this->additional['paymentIntentSecret'];
        }

        return $data;
    }
}
