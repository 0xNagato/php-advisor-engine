<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Concierge;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConciergeStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public ?Concierge $concierge;

    public static function canView(): bool
    {
        $currentRoute = request()?->route()?->getName();

        if (($currentRoute === 'filament.admin.pages.concierge-dashboard') && auth()->user()->hasRole('concierge')) {
            return true;
        }

        if ($currentRoute === 'filament.admin.resources.concierges.view' && auth()->user()->hasRole('super_admin')) {
            return true;
        }

        return false;
    }

    public function mount(): void
    {

    }

    protected function getStats(): array
    {
        $startDate = now()->subDays(30);
        $endDate = now();
        $daysInRange = $startDate->diffInDays($endDate);
        $dateRange = Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('M d');


        $currentConciergeId = $this->concierge->id ?? auth()->user()->concierge->id;

        $query = Booking::where('concierge_id', $currentConciergeId)
            ->whereBetween('booking_at', [$startDate, $endDate]);

        $overallEarnings = $query->sum('total_fee');

        $conciergeEarnings = $query->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_concierge / 100);

            return $carry + $earnings;
        }, 0);

        $charityEarnings = $query->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_charity / 100);

            return $carry + $earnings;
        }, 0);

        $currentBookings = $query->count();

        $prevStartDate = $startDate->copy()->subDays($daysInRange);
        $prevEndDate = $endDate->copy()->subDays($daysInRange);

        $prevQuery = Booking::where('concierge_id', $currentConciergeId)
            ->whereBetween('booking_at', [$prevStartDate, $prevEndDate]);

        $prevOverallEarnings = $prevQuery->sum('total_fee');

        $prevConciergeEarnings = $prevQuery->get()->reduce(function (int $carry, $booking) {
            $earnings = $booking->total_fee * ($booking->payout_concierge / 100);

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

        $conciergeEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->reduce(function ($carry, $booking) {
                $earnings = $booking->total_fee * ($booking->payout_concierge / 100);

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
        $conciergeIncrease = $conciergeEarnings - $prevConciergeEarnings;
        $charityIncrease = $charityEarnings - $prevCharityEarnings;

        $overallEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->sum('total_fee'));

        $conciergeEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->reduce(function ($carry, $booking) {
                $earnings = $booking->total_fee * ($booking->payout_concierge / 100);

                return $carry + $earnings;
            }, 0));

        $charityEarningsPerDay = $query->get()
            ->groupBy(fn($booking) => Carbon::parse($booking->booking_at)->format('Y-m-d'))
            ->map(fn($bookings) => $bookings->reduce(function ($carry, $booking) {
                $earnings = $booking->total_fee * ($booking->payout_charity / 100);

                return $carry + $earnings;
            }, 0));

        $bookingsIncrease = $currentBookings - $prevBookings;

        return [
            Stat::make("Earnings $dateRange", money($conciergeEarnings))
                ->description(money($conciergeIncrease))
                ->descriptionIcon($conciergeIncrease >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($conciergeIncrease >= 0 ? 'success' : 'danger'),
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
