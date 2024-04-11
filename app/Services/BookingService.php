<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingPaid;
use App\Models\Booking;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class BookingService
{
    /**
     * @throws ApiErrorException
     * @throws NumberParseException
     *
     * @todo Add type hint for $form with DataTransferObject
     */
    public function processBooking(Booking $booking, $form): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeCustomer = Customer::create([
            'name' => $form['first_name'] . ' ' . $form['last_name'],
            'phone' => $form['phone'],
            'email' => $form['email'],
            'source' => $form['token'],
        ]);

        $stripeCharge = Charge::create([
            'amount' => $booking->total_with_tax_in_cents,
            'currency' => 'usd',
            'customer' => $stripeCustomer->id,
            'description' => 'Booking for ' . $booking->restaurant->restaurant_name,
        ]);

        $phoneUtil = PhoneNumberUtil::getInstance();
        $numberProto = $phoneUtil->parse($form['phone'], 'US');
        $formattedPhone = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);

        $booking->update([
            'guest_first_name' => $form['first_name'],
            'guest_last_name' => $form['last_name'],
            'guest_phone' => $formattedPhone,
            'guest_email' => $form['email'],
            'status' => BookingStatus::CONFIRMED,
            'stripe_charge' => $stripeCharge->toArray(),
            'stripe_charge_id' => $stripeCharge->id,
            'confirmed_at' => now(),
        ]);

        if (!app()->runningInConsole()) {
            BookingPaid::dispatch($booking);
        }
    }
}
