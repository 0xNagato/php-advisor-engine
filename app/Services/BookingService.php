<?php

namespace App\Services;

use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Enums\BookingStatus;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\ConciergeFirstBooking;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Traits\FormatsPhoneNumber;
use Exception;
use Illuminate\Support\Facades\Activity;
use Illuminate\Support\Facades\DB;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class BookingService
{
    use FormatsPhoneNumber;

    /**
     * @throws ApiErrorException
     */
    public function processBooking(Booking $booking, $form): void
    {
        $stripeCharge = $this->handleStripeCharge($booking, $form);
        $this->updateBooking($booking, $form, $stripeCharge);

        $booking->notify(new CustomerBookingConfirmed);
        SendConfirmationToVenueContacts::run($booking);

        if ($booking->concierge && $booking->concierge->bookings()->count() === 1) {
            $booking->concierge->user->notify(new ConciergeFirstBooking($booking));
        }

        BookingPaid::dispatch($booking);
    }

    public function convertToNonPrime(Booking $booking): void
    {
        if (! $booking->is_prime) {
            return;
        }

        // Log the state before conversion
        $oldState = [
            'is_prime' => $booking->is_prime,
            'venue_earnings' => $booking->venue_earnings,
            'concierge_earnings' => $booking->concierge_earnings,
            'platform_earnings' => $booking->platform_earnings,
            'total_fee' => $booking->total_fee,
            'total_with_tax_in_cents' => $booking->total_with_tax_in_cents,
        ];

        DB::beginTransaction();
        try {
            // Delete earnings
            $booking->earnings()->delete();

            // Update booking
            $booking->venue_earnings = 0;
            $booking->concierge_earnings = 0;
            $booking->platform_earnings = 0;
            $booking->partner_concierge_id = null;
            $booking->partner_venue_id = null;
            $booking->partner_concierge_fee = 0;
            $booking->partner_venue_fee = 0;
            $booking->is_prime = 0;
            $booking->total_fee = 0;
            $booking->total_with_tax_in_cents = 0;

            $meta = $booking->meta ?? [];
            $meta['converted_to_non_prime_at'] = now();
            $booking->meta = $meta;

            $booking->save();

            // Log the conversion activity
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'guest_name' => $booking->guest_name,
                    'venue_name' => $booking->venue->name,
                    'booking_time' => $booking->booking_at->format('M d, Y h:i A'),
                    'guest_count' => $booking->guest_count,
                    'previous_state' => $oldState,
                    'converted_by' => auth()->user()?->name ?? 'System',
                    'converted_by_id' => auth()->id(),
                ])
                ->log('Booking converted from Prime to Non-Prime');

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function convertToPrime(Booking $booking): void
    {
        if ($booking->is_prime) {
            return;
        }

        // Log the state before conversion
        $oldState = [
            'is_prime' => $booking->is_prime,
            'venue_earnings' => $booking->venue_earnings,
            'concierge_earnings' => $booking->concierge_earnings,
            'platform_earnings' => $booking->platform_earnings,
            'total_fee' => $booking->total_fee,
            'total_with_tax_in_cents' => $booking->total_with_tax_in_cents,
        ];

        DB::beginTransaction();
        try {
            // Delete earnings
            $booking->earnings()->delete();

            // Update booking prime property
            $booking->is_prime = 1;

            $meta = $booking->meta ?? [];
            $meta['converted_to_prime_at'] = now();
            $booking->meta = $meta;

            $booking->save();

            $booking->total_fee = $this->getPrimeTotalFee($booking);
            $booking->venue_earnings =
                $booking->total_fee *
                ($booking->venue->payout_venue / 100);
            $booking->concierge_earnings =
                $booking->total_fee *
                ($booking->concierge->payout_percentage / 100);
            $booking->save();

            $taxData = app(SalesTaxService::class)->calculateTax(
                $booking->venue->region,
                $booking->total_fee,
                noTax: config('app.no_tax')
            );

            $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

            $booking->update([
                'tax' => $taxData->tax,
                'tax_amount_in_cents' => $taxData->amountInCents,
                'city' => $taxData->region,
                'total_with_tax_in_cents' => $totalWithTaxInCents,
            ]);

            // Log the conversion activity
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'guest_name' => $booking->guest_name,
                    'venue_name' => $booking->venue->name,
                    'booking_time' => $booking->booking_at->format('M d, Y h:i A'),
                    'guest_count' => $booking->guest_count,
                    'previous_state' => $oldState,
                    'new_state' => [
                        'total_fee' => $booking->total_fee,
                        'venue_earnings' => $booking->venue_earnings,
                        'concierge_earnings' => $booking->concierge_earnings,
                        'total_with_tax_in_cents' => $totalWithTaxInCents,
                        'tax_amount' => $taxData->amountInCents,
                    ],
                    'converted_by' => auth()->user()?->name ?? 'System',
                    'converted_by_id' => auth()->id(),
                ])
                ->log('Booking converted from Non-Prime to Prime');

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getPrimeTotalFee(Booking $booking): int
    {
        $booking->load('schedule.venue');
        $schedule = $booking->schedule;
        $venue = $schedule->venue;

        $extraPeople = max(0, $booking->guest_count - 2);

        $extraFee = $extraPeople * $venue->increment_fee;

        return ($schedule->effective_fee + $extraFee) * 100;
    }

    /**
     * @throws ApiErrorException
     */
    private function handleStripeCharge(Booking $booking, $form)
    {
        if (! $booking->prime_time) {
            return null;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeCustomer = Customer::create([
            'name' => $form['first_name'].' '.$form['last_name'],
            'phone' => $form['phone'],
            'email' => $form['email'],
            'source' => $form['token'],
        ]);

        $region = Region::query()->find($booking->venue->region);

        return Charge::create([
            'amount' => $booking->total_with_tax_in_cents,
            'currency' => $region->currency,
            'customer' => $stripeCustomer->id,
            'description' => 'Booking for '.$booking->venue->name,
        ]);
    }

    private function updateBooking(Booking $booking, $form, $stripeCharge): void
    {
        $formattedPhone = $this->getInternationalFormattedPhoneNumber($form['phone']);

        $booking->update([
            'guest_first_name' => $form['first_name'],
            'guest_last_name' => $form['last_name'],
            'guest_phone' => $formattedPhone,
            'guest_email' => $form['email'],
            'status' => BookingStatus::CONFIRMED,
            'stripe_charge' => $booking->prime_time ? $stripeCharge->toArray() : null,
            'stripe_charge_id' => $booking->prime_time ? $stripeCharge->id : null,
            'confirmed_at' => now(),
            'notes' => $form['notes'] ?? null,
        ]);
    }
}
