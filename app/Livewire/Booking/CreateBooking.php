<?php

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

class CreateBooking extends Component
{
    protected static bool $isLazy = false;

    public Booking $booking;

    public bool $isLoading = false;

    public bool $paymentSuccess = false;

    public bool $agreeTerms = true;

    public bool $showModal = false;

    #[Url]
    public $r = '';

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();

        if ($this->booking->clicked_at === null) {
            $this->booking->update(['clicked_at' => now()]);
        }

        if ($this->booking->status === BookingStatus::PENDING) {
            $this->booking->update(['status' => BookingStatus::GUEST_ON_PAGE]);
        }
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.create-booking');
    }

    #[Computed]
    public function isValid(): bool
    {
        return $this->booking->status === BookingStatus::GUEST_ON_PAGE && now()->diffInMinutes($this->booking->created_at) < 10;
    }

    /**
     * @throws ApiErrorException
     */
    public function completeBooking($form): void
    {
        app(BookingService::class)->processBooking($this->booking, $form);

        $this->booking->update(['concierge_referral_type' => $this->r]);

        $this->isLoading = false;
        $this->paymentSuccess = true;
    }
}
