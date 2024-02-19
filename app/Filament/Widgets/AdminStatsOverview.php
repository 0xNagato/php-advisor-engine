<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);
        $daysInRange = $startDate->diffInDays($endDate);
        $dateRange = Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('M d');

        $query = Booking::whereBetween('booking_at', [$startDate, $endDate]);

        $overallEarnings = $query->sum('total_fee');

        $platformEarnings = $query->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_platform / 100);

            return $carry + $earnings;
        }, 0);

        $charityEarnings = $query->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_charity / 100);

            return $carry + $earnings;
        }, 0);

        $prevStartDate = $startDate->copy()->subDays($daysInRange);
        $prevEndDate = $endDate->copy()->subDays($daysInRange);

        $prevQuery = Booking::whereBetween('booking_at', [$prevStartDate, $prevEndDate]);

        $prevOverallEarnings = $prevQuery->sum('total_fee');

        $prevPlatformEarnings = $prevQuery->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_platform / 100);

            return $carry + $earnings;
        }, 0);

        $prevCharityEarnings = $prevQuery->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_charity / 100);

            return $carry + $earnings;
        }, 0);

        $currentBookings = Booking::whereBetween('booking_at', [$startDate, $endDate])->count();

        $prevBookings = Booking::whereBetween('booking_at', [$prevStartDate, $prevEndDate])->count();

        $bookingsIncrease = $currentBookings - $prevBookings;

        // Compare current earnings with previous earnings
        $overallIncrease = $overallEarnings - $prevOverallEarnings;
        $platformIncrease = $platformEarnings - $prevPlatformEarnings;
        $charityIncrease = $charityEarnings - $prevCharityEarnings;

        return [
            Stat::make("Overall $dateRange", money($overallEarnings))
                ->description(money($overallIncrease))
                ->descriptionIcon($overallIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($overallIncrease >= 0 ? 'success' : 'danger'),
            Stat::make("Platform $dateRange", money($platformEarnings))
                ->description(money($platformIncrease))
                ->descriptionIcon($platformIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($platformIncrease >= 0 ? 'success' : 'danger'),
            Stat::make("Charity $dateRange", money($charityEarnings))
                ->description(money($charityIncrease))
                ->descriptionIcon($charityIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($charityIncrease >= 0 ? 'success' : 'danger'),
            Stat::make("Bookings $dateRange", $currentBookings)
                ->description(($bookingsIncrease >= 0 ? '+' : '') . $bookingsIncrease . ' bookings')
                ->descriptionIcon($bookingsIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($bookingsIncrease >= 0 ? 'success' : 'danger'),
        ];
    }
}
