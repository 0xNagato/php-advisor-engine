<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{

    use InteractsWithPageFilters;

    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $dateRange = $startDate->format('M j') . ' - ' . $endDate->format('M j');

        // Initialize the query
        $query = Booking::query();

        // Check the role of the current user
        if (auth()->user()?->hasRole('super_admin')) {
            // No additional filters for super_admin
        } elseif (auth()->user()?->hasRole('concierge')) {
            // Filter bookings by concierge_id for concierge
            $query->where('concierge_id', auth()->user()->id);
        } elseif (auth()->user()?->hasRole('restaurant')) {
            // Filter bookings by restaurant_id for restaurant
            $query->whereHas('schedule', function ($query) {
                $query->where('restaurant_id', auth()->user()->restaurant->id);
            });
        }

        // Calculate total earnings and total bookings between startDate and endDate
        $totalEarnings = (clone $query)->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_fee');

        $totalBookings = (clone $query)->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Calculate the start and end dates for the previous period
        $days = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($days);
        $prevEndDate = $endDate->copy()->subDays($days);

        // Calculate total earnings and total bookings for the previous period
        $prevTotalEarnings = (clone $query)->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('total_fee');

        $prevTotalBookings = (clone $query)->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->count();

        // Calculate the increase or decrease in earnings and bookings
        $earningsIncrease = $totalEarnings - $prevTotalEarnings;
        $bookingsIncrease = $totalBookings - $prevTotalBookings;

        // Calculate the earnings and bookings for each day
        $dailyEarnings = (clone $query)->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, sum(total_fee) as earnings')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('earnings', 'date')
            ->all();

        $dailyBookings = (clone $query)->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, count(*) as bookings')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('bookings', 'date')
            ->all();

        return [
            Stat::make("Earned $dateRange", '$' . number_format($totalEarnings / 100, 2))
                ->description('$' . number_format($earningsIncrease / 100, 2) . ' increase')
                ->descriptionIcon($earningsIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart(array_values($dailyEarnings)) // Use the daily earnings as the chart data
                ->color($earningsIncrease >= 0 ? 'success' : 'danger'),
            Stat::make('Bookings', $totalBookings)
                ->description($bookingsIncrease . ' increase')
                ->descriptionIcon($bookingsIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart(array_values($dailyBookings)) // Use the daily bookings as the chart data
                ->color($bookingsIncrease >= 0 ? 'success' : 'danger'),
        ];
    }
}
