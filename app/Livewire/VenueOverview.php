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
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->subDays(30)->startOfDay();
        $endDate = $this->endDate ?? now()->endOfDay();

        $earnings = $this->getEarnings($startDate, $endDate);
        $prevEarnings = $this->getEarnings($startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate);
        $chartData = $this->getChartData($startDate, $endDate);

        $currencySymbol = $this->venue->inRegion->currency_symbol;

        return [
            $this->createStat('Bookings', $earnings['number_of_bookings'], null, $prevEarnings['number_of_bookings'])
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createStat('PRIME Earnings', $earnings['venue_earnings'] / 100, $currencySymbol, $prevEarnings['venue_earnings'] / 100)
                ->chart($chartData['earnings'])
                ->color('success'),
            $this->createStat('Marketing Investment', $earnings['venue_bounty'] / 100, $currencySymbol, $prevEarnings['venue_bounty'] / 100)
                ->chart($chartData['bounty'])
                ->color('warning'),
        ];
    }

    protected function getEarnings($startDate, $endDate): array
    {
        return Earning::query()
            ->whereNotNull('earnings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->venue->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(DISTINCT bookings.id) as number_of_bookings,
                SUM(CASE WHEN earnings.type = "venue" THEN earnings.amount ELSE 0 END) as venue_earnings,
                ABS(SUM(CASE WHEN earnings.type = "venue_paid" THEN earnings.amount ELSE 0 END)) as venue_bounty
            ')
            ->first()
            ?->toArray();
    }

    protected function getChartData($startDate, $endDate): array
    {
        $dailyData = Earning::query()
            ->whereNotNull('earnings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->venue->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(bookings.booking_at) as date'),
                DB::raw('COUNT(DISTINCT bookings.id) as bookings'),
                DB::raw('SUM(CASE WHEN earnings.type = "venue" THEN earnings.amount ELSE 0 END) as earnings'),
                DB::raw('ABS(SUM(CASE WHEN earnings.type = "venue_paid" THEN earnings.amount ELSE 0 END)) as bounty')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'bookings' => $dailyData->pluck('bookings')->toArray(),
            'earnings' => $dailyData->pluck('earnings')->map(fn ($amount) => $amount / 100)->toArray(),
            'bounty' => $dailyData->pluck('bounty')->map(fn ($amount) => $amount / 100)->toArray(),
        ];
    }

    protected function createStat(string $label, float $value, ?string $currencySymbol = null, float $previousValue = 0): Stat
    {
        $formattedValue = $currencySymbol ? $currencySymbol.number_format($value, 2) : number_format($value);

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
