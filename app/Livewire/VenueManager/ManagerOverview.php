<?php

namespace App\Livewire\VenueManager;

use App\Models\Booking;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ManagerOverview extends Widget
{
    public Collection $venues;

    public Carbon $startDate;

    public Carbon $endDate;

    protected static string $view = 'livewire.venue-manager.manager-overview';

    public function getBookingsCount(): int
    {
        return Booking::query()
            ->whereIn('venue_id', $this->venues->pluck('id'))
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->count();
    }

    public function getTotalRevenue(): float
    {
        return Booking::query()
            ->whereIn('venue_id', $this->venues->pluck('id'))
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->sum('total_amount');
    }

    // Add more aggregated metrics as needed
}
