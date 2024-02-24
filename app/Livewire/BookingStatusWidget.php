<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Component;

class BookingStatusWidget extends Component
{
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
        if ($this->booking->status === BookingStatus::PENDING) {
            return 'The booking is currently pending. Please confirm it as soon as possible.';
        }

        if ($this->booking->status === BookingStatus::CONFIRMED) {
            return 'The booking has been confirmed!';
        }

        if ($this->booking->status === BookingStatus::GUEST_ON_PAGE) {
            return 'The guest is currently on the page. Please assist them with their booking.';
        }

        if ($this->booking->status === BookingStatus::COMPLETED) {
            return 'The booking has been completed successfully.';
        }

        if ($this->booking->status === BookingStatus::CANCELLED) {
            return 'The booking has been cancelled. Please check if there was a mistake.';
        }
    }
}
