<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RestaurantStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('restaurant');
    }

    protected function getStats(): array
    {
        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);
        $daysInRange = $startDate->diffInDays($endDate);
        $dateRange = Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('M d');

        $currentRestaurantId = auth()->user()->restaurant->id;

        $query = Booking::join('schedules', 'bookings.schedule_id', '=', 'schedules.id')
            ->where('schedules.restaurant_id', $currentRestaurantId)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate]);

        $overallEarnings = $query->sum('total_fee');

        $restaurantEarnings = $query->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_restaurant / 100);

            return $carry + $earnings;
        }, 0);

        $charityEarnings = $query->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_charity / 100);

            return $carry + $earnings;
        }, 0);

        $currentBookings = $query->count();

        $prevStartDate = $startDate->copy()->subDays($daysInRange);
        $prevEndDate = $endDate->copy()->subDays($daysInRange);

        $prevQuery = Booking::join('schedules', 'bookings.schedule_id', '=', 'schedules.id')
            ->where('schedules.restaurant_id', $currentRestaurantId)
            ->whereBetween('booking_at', [$prevStartDate, $prevEndDate]);

        $prevOverallEarnings = $prevQuery->sum('total_fee');

        $prevRestaurantEarnings = $prevQuery->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_restaurant / 100);

            return $carry + $earnings;
        }, 0);

        $prevCharityEarnings = $prevQuery->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_charity / 100);

            return $carry + $earnings;
        }, 0);

        $prevBookings = $prevQuery->count();

        $overallEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->sum('total_fee'));

        $restaurantEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->reduce(function ($carry, $booking) {
                $earnings = $booking->total_fee * ($booking->payout_restaurant / 100);

                return $carry + $earnings;
            }, 0));

        $charityEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->reduce(function ($carry, $booking) {
                $earnings = $booking->total_fee * ($booking->payout_charity / 100);

                return $carry + $earnings;
            }, 0));

        $bookingsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->count());

        // Compare current earnings with previous earnings
        $overallIncrease = $overallEarnings - $prevOverallEarnings;
        $restaurantIncrease = $restaurantEarnings - $prevRestaurantEarnings;
        $charityIncrease = $charityEarnings - $prevCharityEarnings;
        $bookingsIncrease = $currentBookings - $prevBookings;

        return [
            Stat::make("Earnings $dateRange", money($restaurantEarnings))
                ->description(money($restaurantIncrease))
                ->descriptionIcon($restaurantIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($restaurantIncrease >= 0 ? 'success' : 'danger'),
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
