<?php

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Component;

class BookingStatusWidget extends Component
{
    protected static bool $isLazy = false;

    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.booking-status-widget', [
            'booking' => $this->booking,
        ]);
    }

    public function status(): string
    {
        return match ($this->booking->status) {
            BookingStatus::PENDING => 'The booking is currently pending. Confirm it as soon as possible.',
            BookingStatus::CONFIRMED => 'The booking has been confirmed!',
            BookingStatus::GUEST_ON_PAGE => 'The guest is currently on the page. Assist them with their booking.',
            BookingStatus::COMPLETED => 'The booking has been completed successfully.',
            BookingStatus::CANCELLED => 'The booking has been cancelled. Check if there was a mistake.',
            BookingStatus::NO_SHOW => 'The guest did not show up for the booking.',
        };
    }
}
