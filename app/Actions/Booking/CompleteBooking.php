<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Traits\FormatsPhoneNumber;
use Exception;
use Lorisleiva\Actions\Concerns\AsAction;
use Stripe\StripeClient;

class CompleteBooking
{
    use AsAction;
    use FormatsPhoneNumber;

    public function handle(Booking $booking, string $paymentIntentId, array $formData): array
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId, ['expand' => ['latest_charge']]);

            throw_if($paymentIntent->status !== 'succeeded', new Exception('Payment not successful'));

            $stripeCharge = $paymentIntent->latest_charge;

            $formattedPhone = $this->getInternationalFormattedPhoneNumber($formData['phone']);

            $booking->update([
                'concierge_referral_type' => $formData['r'],
                'guest_first_name' => $formData['firstName'],
                'guest_last_name' => $formData['lastName'],
                'guest_phone' => $formattedPhone,
                'guest_email' => $formData['email'],
                'status' => BookingStatus::CONFIRMED,
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_charge_id' => $stripeCharge->id,
                'stripe_charge' => $stripeCharge->toArray(),
                'confirmed_at' => now(),
                'notes' => $formData['notes'] ?? null,
            ]);

            $booking->notify(new CustomerBookingConfirmed);
            SendConfirmationToVenueContacts::run($booking);

            BookingPaid::dispatch($booking);

            return ['success' => true, 'message' => 'Booking confirmed successfully'];
        } catch (Exception $e) {
            logger()->error($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
