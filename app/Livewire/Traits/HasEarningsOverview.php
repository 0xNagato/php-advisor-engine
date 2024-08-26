<?php

namespace App\Livewire\Traits;

use App\Services\CurrencyConversionService;
use Filament\Widgets\StatsOverviewWidget\Stat;

trait HasEarningsOverview
{
    protected function getCommonStats(array $earnings, array $prevEarnings, array $chartData): array
    {
        $currencyService = app(CurrencyConversionService::class);
        $totalEarningsUSD = $currencyService->convertToUSD($earnings['earnings']);
        $prevTotalEarningsUSD = $currencyService->convertToUSD($prevEarnings['earnings']);

        return [
            $this->createStat('Total Bookings', $earnings['number_of_bookings'], null, $prevEarnings['number_of_bookings'])
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createEarningsStat($totalEarningsUSD, $prevTotalEarningsUSD, $earnings['earnings'])
                ->chart($chartData['earnings'])
                ->color('success'),
            $this->createStat('Average Earning per Booking', $this->getAverageBookingValue($earnings), 'USD', $this->getAverageBookingValue($prevEarnings))
                ->chart($chartData['avg_booking_value'])
                ->color('info'),
        ];
    }

    protected function createStat(string $label, float $value, ?string $currency = null, float $previousValue = 0): Stat
    {
        $currencySymbol = $this->getCurrencySymbol($currency);
        $formattedValue = $currency
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

    protected function createEarningsStat(float $totalEarningsUSD, float $prevTotalEarningsUSD, array $currencyBreakdown): Stat
    {
        $stat = Stat::make('Earnings (converted to USD)', '$'.number_format($totalEarningsUSD, 2));

        $breakdownDescription = collect($currencyBreakdown)
            ->map(fn ($amount, $currency) => $this->getCurrencySymbol($currency).number_format($amount / 100, 2))
            ->implode(', ');

        $stat->description($breakdownDescription)
            ->descriptionIcon('heroicon-m-currency-dollar');

        if ($prevTotalEarningsUSD > 0) {
            $percentageChange = (($totalEarningsUSD - $prevTotalEarningsUSD) / $prevTotalEarningsUSD) * 100;
            $stat->color($percentageChange >= 0 ? 'success' : 'danger');
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
            default => $currency ?? '',
        };
    }

    protected function getAverageBookingValue(array $earnings): float
    {
        if ($earnings['number_of_bookings'] === 0) {
            return 0;
        }

        $currencyService = app(CurrencyConversionService::class);
        $totalEarningsUSD = $currencyService->convertToUSD($earnings['earnings']);

        return $totalEarningsUSD / $earnings['number_of_bookings'];
    }
}
