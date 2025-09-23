<?php

namespace App\Actions\Booking;

use App\Actions\Risk\ProcessBookingRisk;
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

            // Apply customer attribution logic for returning customers
            UpdateBookingConciergeAttribution::run($booking, $formattedPhone);

            $booking->update([
                'concierge_referral_type' => $formData['r'],
                'guest_first_name' => $formData['firstName'],
                'guest_last_name' => $formData['lastName'],
                'guest_phone' => $formattedPhone,
                'guest_email' => $formData['email'],
                'status' => BookingStatus::CONFIRMED,  // Default to confirmed, ProcessBookingRisk will override if needed
                'stripe_payment_intent_id' => $paymentIntentId ?: null,
                'stripe_charge_id' => $stripeCharge?->id,
                'stripe_charge' => $stripeCharge?->toArray(),
                'confirmed_at' => now(),
                'notes' => $formData['notes'] ?? null,
            ]);

            // Process risk scoring - this will set the status
            ProcessBookingRisk::run($booking);

            // Reload to get updated status
            $booking->refresh();

            // Only send notifications if booking is not on risk hold
            if (! $booking->isOnRiskHold()) {
                if ($booking->is_non_prime_big_group) {
                    $booking->notify(new CustomerBookingRequestReceived);
                } else {
                    $booking->notify(new CustomerBookingConfirmed);
                }

                // Only send regular confirmation SMS if booking doesn't qualify for auto-approval
                // Auto-approval eligible bookings will get their notification after platform sync
                if (! AutoApproveSmallPartyBooking::qualifiesForAutoApproval($booking)) {
                    SendConfirmationToVenueContacts::run($booking);
                }

                if ($booking->concierge && $booking->concierge->bookings()->count() === 1) {
                    $booking->concierge->user->notify(new ConciergeFirstBooking($booking));
                }

                BookingConfirmed::dispatch($booking->load('schedule', 'venue'));
            }

            // Always dispatch BookingPaid event regardless of risk hold
            BookingPaid::dispatch($booking);

            return ['success' => true, 'message' => 'Booking confirmed successfully'];
        } catch (Exception $e) {
            logger()->error($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
