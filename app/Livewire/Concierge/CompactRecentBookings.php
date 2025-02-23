<?php

namespace App\Livewire\Concierge;

use App\Models\Booking;
use Illuminate\View\View;
use Livewire\Component;

class CompactRecentBookings extends Component
{
    public function render(): View
    {
        $recentBookings = Booking::query()
            ->confirmed()
            ->where('concierge_id', auth()->user()->concierge->id)
            ->with(['schedule.venue', 'earnings'])
            ->orderByDesc('booking_at')
            ->limit(5)
            ->get();

        return view('livewire.concierge.compact-recent-bookings', [
            'bookings' => $recentBookings,
        ]);
    }
}
