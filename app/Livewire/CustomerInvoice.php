<?php

namespace App\Livewire;

use App\Models\Booking;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Livewire\Component;

class CustomerInvoice extends Component
{
    public Booking $booking;

    public function mount(string $token): void
    {
        $this->booking = Booking::where('uuid', $token)->firstOrFail();
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|Factory|View|Application
    {
        return view('livewire.customer-invoice');
    }
}
