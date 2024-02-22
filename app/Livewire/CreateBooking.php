<?php

namespace App\Livewire;

use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
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

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();
    }

    #[Layout('layouts.empty')]
    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.create-booking');
    }

    /**
     * @throws ApiErrorException
     */
    public function completeBooking(array $form): void
    {
        Stripe::setApiKey(config('cashier.secret'));

        $stripeCustomer = Customer::create([
            'name' => $form['firstName'] . ' ' . $form['lastName'],
            'phone' => $form['phone'],
            'source' => $form['token']['id'],
        ]);

        $stripeCharge = Charge::create([
            'amount' => $this->booking->total_fee,
            'currency' => 'usd',
            'customer' => $stripeCustomer->id,
            'description' => 'Booking for ' . $this->booking->schedule->restaurant->name,
        ]);

        ds($stripeCharge);
        $this->isLoading = false;
        $this->paymentSuccess = true;
    }
}
