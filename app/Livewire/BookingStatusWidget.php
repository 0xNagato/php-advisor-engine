<?php

namespace App\Livewire;

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
}
