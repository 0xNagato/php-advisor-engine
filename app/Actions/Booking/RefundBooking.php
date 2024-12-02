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

    public function handle(Booking $booking, ?string $reason = null, ?int $amount = null): array
    {
        try {
            if (! $booking->stripe_charge_id && ! $booking->stripe_payment_intent_id) {
                throw new Exception('No payment information found for this booking.');
            }

            $stripe = new StripeClient(config('services.stripe.secret'));

            $refundParams = [
                'reason' => $reason,
                'amount' => $amount ?? $booking->total_with_tax_in_cents,
            ];

            if ($booking->stripe_payment_intent_id) {
                $refundParams['payment_intent'] = $booking->stripe_payment_intent_id;
            } else {
                $refundParams['charge'] = $booking->stripe_charge_id;
            }

            $refund = $stripe->refunds->create($refundParams);

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
