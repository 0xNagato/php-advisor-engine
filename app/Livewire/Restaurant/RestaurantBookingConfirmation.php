<?php

namespace App\Livewire\Restaurant;

use App\Models\Booking;
use Livewire\Component;

class RestaurantBookingConfirmation extends Component
{
    public Booking $booking;

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();

        if ($this->booking->restaurant_confirmed_at === null) {
            $this->booking->update(['restaurant_confirmed_at' => now()]);
        }
    }

    public function render()
    {
        return view('livewire.restaurant-booking-confirmation');
    }
}
