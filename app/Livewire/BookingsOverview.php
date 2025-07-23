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
                DB::raw('SUM(CASE WHEN is_prime = true THEN total_fee - total_refunded ELSE 0 END) as prime_bookings'),
                DB::raw('SUM(CASE WHEN is_prime = true THEN total_fee - total_refunded ELSE ABS(venue_earnings) END) as gross_revenue'),
                DB::raw('SUM(platform_earnings - platform_earnings_refunded) as prima_share'),
                'currency'
            )
            ->groupBy('currency')
            ->get();

        $totalBookings = $bookings->sum('count');

        $currencyService = app(CurrencyConversionService::class);
        $grossRevenueUSD = $currencyService->convertToUSD($bookings->pluck('gross_revenue', 'currency')->toArray());
        $primaShareUSD = $currencyService->convertToUSD($bookings->pluck('prima_share', 'currency')->toArray());

        // Pass the converted UTC dates into the chart data query
        $chartData = $this->getChartData($startDateUTC, $endDateUTC);

        $primeBookingsUSD = $currencyService->convertToUSD($bookings->pluck('prime_bookings', 'currency')->toArray());

        return [
            $this->createStat('Bookings', $totalBookings)
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createStat('PRIME Bookings', $primeBookingsUSD, 'USD',
                $bookings->pluck('prime_bookings', 'currency')->toArray())
                ->chart($chartData['amounts'])
                ->color('success'),
            $this->createCompoundStat('Revenue', $grossRevenueUSD, $primaShareUSD, 'USD',
                $bookings->pluck('gross_revenue', 'currency')->toArray(),
                $bookings->pluck('prima_share', 'currency')->toArray())
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
                DB::raw('SUM(CASE WHEN is_prime = true THEN total_fee ELSE 0 END) as prime_bookings'),
                DB::raw('SUM(CASE WHEN is_prime = true THEN total_fee ELSE ABS(venue_earnings) END) as gross_revenue'),
                DB::raw('SUM(platform_earnings) as prima_share'),
                'currency'
            )
            ->groupBy('date', 'currency')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $bookings = $dayData->sum('bookings');
            $primeBookings = $currencyService->convertToUSD($dayData->pluck('prime_bookings', 'currency')->toArray());
            $grossRevenue = $currencyService->convertToUSD($dayData->pluck('gross_revenue', 'currency')->toArray());
            $primaShare = $currencyService->convertToUSD($dayData->pluck('prima_share', 'currency')->toArray());

            return [
                'bookings' => $bookings,
                'amounts' => $primeBookings,
                'earnings' => $grossRevenue,
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

    protected function createCompoundStat(
        string $label,
        float $primaryValue,
        float $secondaryValue,
        ?string $currency = null,
        ?array $primaryBreakdown = null,
        ?array $secondaryBreakdown = null
    ): Stat {
        $currencySymbol = $this->getCurrencySymbol($currency);
        $primaryFormatted = $currency
            ? $currencySymbol.number_format($primaryValue, 2)
            : number_format($primaryValue);

        $secondaryFormatted = $currency
            ? $currencySymbol.number_format($secondaryValue, 2)
            : number_format($secondaryValue);

        // Create description showing both values
        $description = "Gross: {$primaryFormatted} • Share: {$secondaryFormatted}";

        return Stat::make($label, $primaryFormatted)
            ->description($description);
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
