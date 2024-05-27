<?php

namespace App\Livewire\Restaurant;

use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
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

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.restaurant-booking-confirmation');
    }
}
