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
        // Get the user's timezone from auth (or a default)
        $userTimezone = auth()->user()?->timezone ?? config('app.default_timezone');

        // Parse date filters as in the user's timezone then convert to UTC for the query
        $startDateUTC = Carbon::parse(
            $this->filters['startDate'] ?? now($userTimezone)->subDays(30)->format('Y-m-d'),
            $userTimezone
        )->startOfDay()->setTimezone('UTC');

        $endDateUTC = Carbon::parse(
            $this->filters['endDate'] ?? now($userTimezone)->format('Y-m-d'),
            $userTimezone
        )->endOfDay()->setTimezone('UTC');

        $bookings = Booking::query()
            ->whereBetween('confirmed_at', [$startDateUTC, $endDateUTC])
            ->whereIn('status', [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
                BookingStatus::PARTIALLY_REFUNDED,
            ])
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_fee - total_refunded) as total_amount'),
                DB::raw('SUM(platform_earnings - platform_earnings_refunded) as platform_earnings'),
                DB::raw('SUM(CASE WHEN is_prime = true THEN total_fee ELSE (venue_earnings*-1) END) as platform_revenue'),
                'currency'
            )
            ->groupBy('currency')
            ->get();

        $totalBookings = $bookings->sum('count');

        $currencyService = app(CurrencyConversionService::class);
        $totalAmountUSD = $currencyService->convertToUSD($bookings->pluck('total_amount', 'currency')->toArray());
        $platformEarningsUSD = $currencyService->convertToUSD($bookings->pluck('platform_earnings',
            'currency')->toArray());
        $platformRevenueUSD = $currencyService->convertToUSD($bookings->pluck('platform_revenue',
            'currency')->toArray());

        // Pass the converted UTC dates into the chart data query
        $chartData = $this->getChartData($startDateUTC, $endDateUTC);

        return [
            $this->createStat('Bookings', $totalBookings)
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createStat('PRIME Bookings', $totalAmountUSD, 'USD',
                $bookings->pluck('total_amount', 'currency')->toArray())
                ->chart($chartData['amounts'])
                ->color('success'),
            $this->createStat('Platform Revenue', $platformRevenueUSD, 'USD',
                $bookings->pluck('platform_revenue', 'currency')->toArray())
                ->chart($chartData['earnings'])
                ->color('success'),
        ];
    }

    protected function getChartData(Carbon $startDateUTC, Carbon $endDateUTC): array
    {
        $dailyData = Booking::query()
            ->confirmed()
            ->whereBetween('confirmed_at', [$startDateUTC, $endDateUTC])
            ->select(
                DB::raw('DATE(confirmed_at) as date'),
                DB::raw('COUNT(*) as bookings'),
                DB::raw('SUM(total_fee) as total_amount'),
                DB::raw('SUM(platform_earnings) as platform_earnings'),
                DB::raw('SUM(CASE WHEN is_prime = true THEN total_fee ELSE ABS(venue_earnings) END) as platform_revenue'),
                'currency'
            )
            ->groupBy('date', 'currency')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $bookings = $dayData->sum('bookings');
            $amounts = $currencyService->convertToUSD($dayData->pluck('total_amount', 'currency')->toArray());
            $earnings = $currencyService->convertToUSD($dayData->pluck('platform_earnings', 'currency')->toArray());
            $revenue = $currencyService->convertToUSD($dayData->pluck('platform_revenue', 'currency')->toArray());

            return [
                'bookings' => $bookings,
                'amounts' => $amounts,
                'earnings' => $earnings,
                'revenue' => $revenue,
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
