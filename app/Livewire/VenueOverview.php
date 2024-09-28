<?php

namespace App\Livewire;

use App\Models\Earning;
use App\Models\Venue;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class VenueOverview extends BaseWidget
{
    public ?Venue $venue = null;

    #[Reactive]
    public ?string $startDate = null;

    #[Reactive]
    public ?string $endDate = null;

    protected function getStats(): array
    {
        $startDate = Carbon::parse($this->startDate ?? now()->subDays(30))->startOfDay();
        $endDate = Carbon::parse($this->endDate ?? now())->endOfDay();

        $earnings = $this->getEarnings($startDate, $endDate);
        $prevEarnings = $this->getEarnings($startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate);
        $chartData = $this->getChartData($startDate, $endDate);

        $currencySymbol = $this->venue->inRegion->currency_symbol;

        return [
            $this->createStat('Bookings', $earnings['number_of_bookings'], null, $prevEarnings['number_of_bookings'])
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createStat('Earnings', $earnings['total_earnings'], $currencySymbol, $prevEarnings['total_earnings'])
                ->chart($chartData['earnings'])
                ->color('success'),
            $this->createStat('Avg. Earning per Booking', $earnings['avg_earning_per_booking'], $currencySymbol, $prevEarnings['avg_earning_per_booking'])
                ->chart($chartData['avg_earning_per_booking'])
                ->color('info'),
        ];
    }

    protected function getEarnings($startDate, $endDate): array
    {
        $earnings = Earning::query()
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->venue->user_id)
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->where('earnings.type', 'venue')
            ->select(
                DB::raw('COUNT(DISTINCT bookings.id) as number_of_bookings'),
                DB::raw('SUM(earnings.amount) as total_earnings')
            )
            ->first();

        $numberOfBookings = $earnings->number_of_bookings ?? 0;
        $totalEarnings = $earnings->total_earnings ?? 0;

        return [
            'number_of_bookings' => $numberOfBookings,
            'total_earnings' => $totalEarnings / 100, // Convert cents to dollars
            'avg_earning_per_booking' => $numberOfBookings > 0 ? ($totalEarnings / $numberOfBookings) / 100 : 0,
        ];
    }

    protected function getChartData($startDate, $endDate): array
    {
        $dailyData = Earning::query()
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->venue->user_id)
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->where('earnings.type', 'venue')
            ->selectRaw('DATE(bookings.confirmed_at) as date, COUNT(DISTINCT bookings.id) as bookings, SUM(earnings.amount) as total_earnings')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = $dailyData->map(fn ($item) => [
            'bookings' => $item->bookings,
            'earnings' => $item->total_earnings / 100, // Convert cents to dollars
            'avg_earning_per_booking' => $item->bookings > 0 ? ($item->total_earnings / $item->bookings) / 100 : 0,
        ]);

        return [
            'bookings' => $chartData->pluck('bookings')->toArray(),
            'earnings' => $chartData->pluck('earnings')->toArray(),
            'avg_earning_per_booking' => $chartData->pluck('avg_earning_per_booking')->toArray(),
        ];
    }

    protected function createStat(string $label, float $value, ?string $currencySymbol = null, float $previousValue = 0): Stat
    {
        $formattedValue = $currencySymbol
            ? $currencySymbol.number_format($value, 2)
            : number_format($value);

        $stat = Stat::make($label, $formattedValue);

        if ($previousValue > 0) {
            $percentageChange = (($value - $previousValue) / $previousValue) * 100;
            $stat->description(sprintf('%+.2f%%', $percentageChange))
                ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($percentageChange >= 0 ? 'success' : 'danger');
        }

        return $stat;
    }
}
