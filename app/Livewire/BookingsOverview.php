<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BookingsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $startDate = Carbon::parse($this->filters['startDate'] ?? now()->subDays(30))->startOfDay();
        $endDate = Carbon::parse($this->filters['endDate'] ?? now())->endOfDay();

        $bookings = Booking::query()
            ->whereBetween('confirmed_at', [$startDate, $endDate])
            ->whereIn('status',
                [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED, BookingStatus::PARTIALLY_REFUNDED])
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_fee - total_refunded) as total_amount'),
                DB::raw('SUM(platform_earnings - platform_earnings_refunded) as platform_earnings'),
                'currency'
            )
            ->groupBy('currency')
            ->get();

        $totalBookings = $bookings->sum('count');

        $currencyService = app(CurrencyConversionService::class);
        $totalAmountUSD = $currencyService->convertToUSD($bookings->pluck('total_amount', 'currency')->toArray());
        $platformEarningsUSD = $currencyService->convertToUSD($bookings->pluck('platform_earnings',
            'currency')->toArray());

        $chartData = $this->getChartData($startDate, $endDate);

        return [
            $this->createStat('Bookings', $totalBookings)
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createStat('Total Amount', $totalAmountUSD, 'USD',
                $bookings->pluck('total_amount', 'currency')->toArray())
                ->chart($chartData['amounts'])
                ->color('success'),
            $this->createStat('Platform Earnings', $platformEarningsUSD, 'USD',
                $bookings->pluck('platform_earnings', 'currency')->toArray())
                ->chart($chartData['earnings'])
                ->color('success'),
        ];
    }

    protected function getChartData($startDate, $endDate): array
    {
        $dailyData = Booking::query()
            ->confirmed()
            ->whereBetween('confirmed_at', [$startDate, $endDate])
            ->selectRaw('DATE(confirmed_at) as date, COUNT(*) as bookings, SUM(total_fee) as total_amount, SUM(platform_earnings) as platform_earnings, currency')
            ->groupBy('date', 'currency')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $bookings = $dayData->sum('bookings');
            $amounts = $currencyService->convertToUSD($dayData->pluck('total_amount', 'currency')->toArray());
            $earnings = $currencyService->convertToUSD($dayData->pluck('platform_earnings', 'currency')->toArray());

            return [
                'bookings' => $bookings,
                'amounts' => $amounts,
                'earnings' => $earnings,
            ];
        });

        return [
            'bookings' => $chartData->pluck('bookings')->toArray(),
            'amounts' => $chartData->pluck('amounts')->toArray(),
            'earnings' => $chartData->pluck('earnings')->toArray(),
        ];
    }

    protected function createStat(
        string $label,
        float $value,
        ?string $currency = null,
        ?array $currencyBreakdown = null
    ): Stat {
        $currencySymbol = $this->getCurrencySymbol($currency);
        $formattedValue = $currency
            ? $currencySymbol.number_format($value, 2)
            : number_format($value);

        $stat = Stat::make($label, $formattedValue);

        if ($currencyBreakdown) {
            $breakdownDescription = collect($currencyBreakdown)
                ->map(function ($amount, $currency) {
                    $symbol = $this->getCurrencySymbol($currency);

                    return $symbol.number_format($amount / 100, 2);
                })
                ->implode(', ');

            $stat->description($breakdownDescription);
        }

        return $stat;
    }

    protected function getCurrencySymbol(?string $currency): string
    {
        return match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            // Add more currencies as needed
            default => $currency ?? '',
        };
    }
}
