<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Component;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class CreateBooking extends Component
{
    public Booking $booking;

    public bool $isLoading = false;

    public bool $paymentSuccess = false;

    public bool $agreeTerms = true;

    public bool $showModal = false;

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();
        $this->booking->update(['status' => BookingStatus::GUEST_ON_PAGE]);
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.create-booking');
    }

    /**
     * @throws ApiErrorException
     */
    public function completeBooking($form): void
    {
        Stripe::setApiKey(config('cashier.secret'));

        $stripeCustomer = Customer::create([
            'name' => $form['first_name'].' '.$form['last_name'],
            'phone' => $form['phone'],
            'source' => $form['token'],
        ]);

        $stripeCharge = Charge::create([
            'amount' => $this->booking->total_fee,
            'currency' => 'usd',
            'customer' => $stripeCustomer->id,
            'description' => 'Booking for '.$this->booking->schedule->restaurant->restaurant_name,
        ]);

        $this->booking->update([
            'status' => BookingStatus::CONFIRMED,
            'stripe_charge' => $stripeCharge->toArray(),
            'stripe_charge_id' => $stripeCharge->id,
        ]);

        $this->isLoading = false;
        $this->paymentSuccess = true;
    }
}
