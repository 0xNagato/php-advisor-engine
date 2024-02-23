<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Component;

class BookingStatusWidget extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function render()
    {
        return view('livewire.booking-status-widget');
    }

    public function status(): string
    {
        if ($this->booking->status === BookingStatus::PENDING) {
            return 'Pending';
        }

        if ($this->booking->status === BookingStatus::CONFIRMED) {
            return 'Confirmed';
        }

        if ($this->booking->status === BookingStatus::GUEST_ON_PAGE) {
            return 'Guest on page';
        }

        if ($this->booking->status === BookingStatus::COMPLETED) {
            return 'Completed';
        }

        if ($this->booking->status === BookingStatus::CANCELLED) {
            return 'Cancelled';
        }
    }

    public function color(): string
    {
        if ($this->booking->status === BookingStatus::PENDING) {
            return 'bg-yellow-50 border-yellow-300';
        }

        if ($this->booking->status === BookingStatus::CONFIRMED) {
            return 'bg-green-50 border-green-300';
        }

        if ($this->booking->status === BookingStatus::GUEST_ON_PAGE) {
            return 'bg-blue-50 border-blue-300';
        }

        if ($this->booking->status === BookingStatus::COMPLETED) {
            return 'bg-green-50 border-green-300';
        }

        if ($this->booking->status === BookingStatus::CANCELLED) {
            return 'bg-red-50 border-red-300';
        }
    }
}
