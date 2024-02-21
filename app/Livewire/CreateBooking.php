<?php

namespace App\Livewire;

use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CreateBooking extends Component
{
    public Booking $booking;

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();
    }

    #[Layout('layouts.empty')]
    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.create-booking');
    }

    public function save(): void
    {

    }
}
