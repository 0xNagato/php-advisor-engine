<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class CreateStripePaymentIntent
{
    /**
     * Create a Stripe payment intent for a booking
     *
     * @param  Booking  $booking  The booking to create payment intent for
     * @return string The payment intent client secret
     *
     * @throws ApiErrorException
     */
    public static function run(Booking $booking): string
    {
        $stripe = app(StripeClient::class);

        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $booking->total_with_tax_in_cents,
            'currency' => $booking->currency,
            'payment_method_types' => ['card', 'link'],
            'metadata' => [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'venue_name' => $booking->venue->name,
                'guest_count' => $booking->guest_count,
                'booking_date' => $booking->booking_at->format('Y-m-d H:i:s'),
            ],
        ]);

        return $paymentIntent->client_secret;
    }
}
