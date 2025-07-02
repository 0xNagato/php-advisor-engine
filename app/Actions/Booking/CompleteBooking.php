<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Notifications\Booking\ConciergeFirstBooking;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Notifications\Booking\CustomerBookingRequestReceived;
use App\Traits\FormatsPhoneNumber;
use Exception;
use Lorisleiva\Actions\Concerns\AsAction;
use Stripe\StripeClient;

class CompleteBooking
{
    use AsAction;
    use FormatsPhoneNumber;

    public function __construct(
        protected readonly StripeClient $stripeClient
    ) {}

    public function handle(Booking $booking, string $paymentIntentId, array $formData): array
    {
        try {
            $stripeCharge = null;

            // Only process Stripe payment for prime bookings with payment intent
            if ($booking->prime_time && $paymentIntentId) {
                $paymentIntent = $this->stripeClient->paymentIntents->retrieve(
                    $paymentIntentId,
                    ['expand' => ['latest_charge']]
                );

                throw_if($paymentIntent->status !== 'succeeded', new Exception('Payment not successful'));

                $stripeCharge = $paymentIntent->latest_charge;
            }

            $formattedPhone = $this->getInternationalFormattedPhoneNumber($formData['phone']);

            $booking->update([
                'concierge_referral_type' => $formData['r'],
                'guest_first_name' => $formData['firstName'],
                'guest_last_name' => $formData['lastName'],
                'guest_phone' => $formattedPhone,
                'guest_email' => $formData['email'],
                'status' => BookingStatus::CONFIRMED,
                'stripe_payment_intent_id' => $paymentIntentId ?: null,
                'stripe_charge_id' => $stripeCharge?->id,
                'stripe_charge' => $stripeCharge?->toArray(),
                'confirmed_at' => now(),
                'notes' => $formData['notes'] ?? null,
            ]);

            if ($booking->is_non_prime_big_group) {
                $booking->notify(new CustomerBookingRequestReceived);
            } else {
                $booking->notify(new CustomerBookingConfirmed);
            }
            SendConfirmationToVenueContacts::run($booking);

            if ($booking->concierge && $booking->concierge->bookings()->count() === 1) {
                $booking->concierge->user->notify(new ConciergeFirstBooking($booking));
            }

            BookingPaid::dispatch($booking);
            BookingConfirmed::dispatch($booking->load('schedule', 'venue'));

            return ['success' => true, 'message' => 'Booking confirmed successfully'];
        } catch (Exception $e) {
            logger()->error($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
