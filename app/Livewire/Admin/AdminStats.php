<?php

namespace App\Livewire\Admin;

use App\Data\AdminStatData;
use App\Models\Booking;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AdminStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.admin.admin-stats';

    protected static bool $isLazy = false;

    public AdminStatData $stats;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Calculate for the current time frame
        $bookingsQuery = Booking::whereBetween('created_at', [$startDate, $endDate]);

        $platformEarnings = $bookingsQuery->sum('platform_earnings');
        $numberOfBookings = $bookingsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevBookingsQuery = Booking::whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $prevPlatformEarnings = $prevBookingsQuery->sum('platform_earnings');
        $prevNumberOfBookings = $prevBookingsQuery->count();
        
        $this->stats = new AdminStatData([
            'current' => [
                'platform_earnings' => $platformEarnings,
                'number_of_bookings' => $numberOfBookings,
            ],
            'previous' => [
                'platform_earnings' => $prevPlatformEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
            ],
            'difference' => [
                'platform_earnings' => $platformEarnings - $prevPlatformEarnings,
                'platform_earnings_up' => $platformEarnings >= $prevPlatformEarnings,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
            ],
            'formatted' => [
                'platform_earnings' => $this->formatNumber($platformEarnings),
                'number_of_bookings' => $numberOfBookings,
                'difference' => [
                    'platform_earnings' => $this->formatNumber($platformEarnings - $prevPlatformEarnings),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                ],
            ],
        ]);
    }

    private function formatNumber($number): string
    {
        return money($number, 'USD');
    }
}
