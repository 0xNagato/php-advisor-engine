<?php

namespace App\Services;

use App\Actions\Booking\SendConfirmationToRestaurantContacts;
use App\Enums\BookingStatus;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\GuestBookingConfirmed;
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
     */
    public function processBooking(Booking $booking, $form): void
    {
        $stripeCharge = $this->handleStripeCharge($booking, $form);
        $this->updateBooking($booking, $form, $stripeCharge);

        $booking->notify(new GuestBookingConfirmed());
        SendConfirmationToRestaurantContacts::run($booking);

        BookingPaid::dispatch($booking);
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

        $region = Region::query()->find($booking->restaurant->region);

        return Charge::create([
            'amount' => $booking->total_with_tax_in_cents,
            'currency' => $region->currency,
            'customer' => $stripeCustomer->id,
            'description' => 'Booking for '.$booking->restaurant->restaurant_name,
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
