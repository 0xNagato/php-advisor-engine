<?php

namespace App\Livewire\Admin;

use App\Models\Booking;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AdminStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.admin.admin-stats';

    protected static bool $isLazy = false;

    public array $stats;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $bookingsQuery = Booking::confirmed()->whereBetween('created_at', [$startDate, $endDate]);
        $numberOfBookings = $bookingsQuery->count();

        $currentEarningsByCurrency = $bookingsQuery->selectRaw('currency, SUM(platform_earnings) as total_earnings')
            ->groupBy('currency')
            ->get();

        $this->stats = compact('currentEarningsByCurrency', 'numberOfBookings');
    }
}
