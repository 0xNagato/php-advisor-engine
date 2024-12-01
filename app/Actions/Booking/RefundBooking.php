<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\Booking\CustomerBookingRefunded;
use Exception;
use Lorisleiva\Actions\Concerns\AsAction;
use Stripe\StripeClient;

class RefundBooking
{
    use AsAction;

    public function handle(Booking $booking, ?string $reason = null): array
    {
        try {
            if (! $booking->stripe_charge_id && ! $booking->stripe_payment_intent_id) {
                throw new Exception('No payment information found for this booking.');
            }

            $stripe = new StripeClient(config('services.stripe.secret'));

            // If we have a payment intent, refund through that
            if ($booking->stripe_payment_intent_id) {
                $refund = $stripe->refunds->create([
                    'payment_intent' => $booking->stripe_payment_intent_id,
                    'reason' => $reason,
                ]);
            } else {
                // Legacy support for old charges
                $refund = $stripe->refunds->create([
                    'charge' => $booking->stripe_charge_id,
                    'reason' => $reason,
                ]);
            }

            $booking->update([
                'status' => BookingStatus::REFUNDED,
                'refund_data' => $refund->toArray(),
                'refunded_at' => now(),
            ]);

            activity()
                ->performedOn($booking)
                ->withProperties([
                    'refund_id' => $refund->id,
                    'amount' => $refund->amount,
                    'reason' => $reason,
                ])
                ->log('Booking refunded');

            $booking->notify(new CustomerBookingRefunded($booking));

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
