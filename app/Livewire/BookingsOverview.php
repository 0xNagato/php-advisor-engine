<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Region;
use App\Services\CurrencyConversionService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BookingsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30)->startOfDay();
        $endDate = $this->filters['endDate'] ?? now()->endOfDay();

        $bookings = Booking::query()
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->where('status', BookingStatus::CONFIRMED)
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_fee) as total_amount'),
                DB::raw('SUM(platform_earnings) as platform_earnings'),
                'currency'
            )
            ->groupBy('currency')
            ->get();

        $totalBookings = $bookings->sum('count');

        $currencyService = app(CurrencyConversionService::class);
        $totalAmountUSD = $currencyService->convertToUSD($bookings->pluck('total_amount', 'currency')->toArray());
        $platformEarningsUSD = $currencyService->convertToUSD($bookings->pluck('platform_earnings', 'currency')->toArray());

        $chartData = $this->getChartData($startDate, $endDate);

        return [
            $this->createStat('Bookings', $totalBookings)
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createStat('Total Amount (converted to USD)', $totalAmountUSD, 'USD', $bookings->pluck('total_amount', 'currency')->toArray())
                ->chart($chartData['amounts'])
                ->color('success'),
            $this->createStat('Platform Earnings (converted to USD)', $platformEarningsUSD, 'USD', $bookings->pluck('platform_earnings', 'currency')->toArray())
                ->chart($chartData['earnings'])
                ->color('success'),
        ];
    }

    protected function getChartData($startDate, $endDate): array
    {
        $dailyData = Booking::query()
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->where('status', BookingStatus::CONFIRMED)
            ->select(
                DB::raw('DATE(booking_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_fee) as total_amount'),
                DB::raw('SUM(platform_earnings) as platform_earnings')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        return [
            'bookings' => $dailyData->pluck('count')->toArray(),
            'amounts' => $dailyData->map(fn ($day) => $currencyService->convertToUSD([$day->total_amount]))->toArray(),
            'earnings' => $dailyData->map(fn ($day) => $currencyService->convertToUSD([$day->platform_earnings]))->toArray(),
        ];
    }

    protected function createStat(string $label, float $value, ?string $currency = null, array $breakdown = []): Stat
    {
        $formattedValue = $currency ? $this->formatCurrency($value, $currency) : number_format($value);
        $description = $this->createBreakdownDescription($breakdown);

        return Stat::make($label, $formattedValue)
            ->description($description);
    }

    protected function createBreakdownDescription(array $breakdown): string
    {
        return collect($breakdown)->map(fn ($amount, $currency) => $this->formatCurrency($amount / 100, $currency))->implode(', ');
    }

    protected function formatCurrency(float $amount, string $currency): string
    {
        $region = Region::query()->where('currency', $currency)->first();

        return $region?->currency_symbol.number_format($amount, 2);
    }
}
