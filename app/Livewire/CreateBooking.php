<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

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
        if ($this->booking->status === BookingStatus::PENDING) {
            $this->booking->update(['status' => BookingStatus::GUEST_ON_PAGE]);
        }
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
        app(BookingService::class)->processBooking($this->booking, $form);

        $this->isLoading = false;
        $this->paymentSuccess = true;
    }
}
