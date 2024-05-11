<?php

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Component;

class CheckIfConfirmed extends Component
{
    public Booking $booking;

    public function boot(): void
    {
        if ($this->booking->status === BookingStatus::CONFIRMED) {
            $this->dispatch('booking-confirmed');
        }
    }

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function render(): string
    {
        return '<span class="hidden" wire:poll.keep-alive.5s></span>';
    }
}
