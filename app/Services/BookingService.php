<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Traits\FormatsPhoneNumber;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class BookingService
{
    use FormatsPhoneNumber;

    /**
     * @throws ApiErrorException
     *
     * @todo Add type hint for $form with DataTransferObject
     */
    public function processBooking(Booking $booking, $form): void
    {
        if ($booking->prime_time) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $stripeCustomer = Customer::create([
                'name' => $form['first_name'].' '.$form['last_name'],
                'phone' => $form['phone'],
                'email' => $form['email'],
                'source' => $form['token'],
            ]);

            $stripeCharge = Charge::create([
                'amount' => $booking->total_with_tax_in_cents,
                'currency' => 'usd',
                'customer' => $stripeCustomer->id,
                'description' => 'Booking for '.$booking->restaurant->restaurant_name,
            ]);
        }

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
        ]);

        BookingPaid::dispatch($booking);
    }
}
