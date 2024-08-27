<?php

namespace App\Livewire;

use App\Models\Earning;
use App\Models\Partner;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class PartnerOverview extends BaseWidget
{
    public ?Partner $partner = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    protected function getStats(): array
    {
        $earnings = $this->getEarnings($this->startDate, $this->endDate);
        $prevEarnings = $this->getEarnings($this->startDate->copy()->subDays($this->startDate->diffInDays($this->endDate)), $this->startDate);
        $chartData = $this->getChartData($this->startDate, $this->endDate);

        $currencyService = app(CurrencyConversionService::class);
        $totalEarningsUSD = $currencyService->convertToUSD($earnings['earnings']);
        $prevTotalEarningsUSD = $currencyService->convertToUSD($prevEarnings['earnings']);

        $avgBookingValue = $this->getAverageBookingValue($this->startDate, $this->endDate);
        $prevAvgBookingValue = $this->getAverageBookingValue($this->startDate->copy()->subDays($this->startDate->diffInDays($this->endDate)), $this->startDate);

        return [
            $this->createStat('Bookings', $earnings['number_of_bookings'], null, $prevEarnings['number_of_bookings'])
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createEarningsStat($totalEarningsUSD, $prevTotalEarningsUSD, $earnings['earnings'])
                ->chart($chartData['earnings'])
                ->color('success'),
            $this->createStat('Avg. Earning (converted to USD)', $avgBookingValue, 'USD', $prevAvgBookingValue)
                ->chart($chartData['avg_booking_value'])
                ->color('info'),
        ];
    }

    protected function getEarnings($startDate, $endDate): array
    {
        $partnerEarningsQuery = Earning::query()
            ->whereNotNull('earnings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->partner->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue']);

        $numberOfBookings = $partnerEarningsQuery->count();

        $partnerEarnings = $partnerEarningsQuery
            ->selectRaw('earnings.currency, SUM(earnings.amount) as total_earnings')
            ->groupBy('earnings.currency')
            ->get();

        return [
            'number_of_bookings' => $numberOfBookings,
            'earnings' => $partnerEarnings->pluck('total_earnings', 'currency')->toArray(),
        ];
    }

    protected function getAverageBookingValue($startDate, $endDate): float
    {
        $result = Earning::query()
            ->whereNotNull('earnings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->partner->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
            ->selectRaw('earnings.currency, AVG(earnings.amount) as average_earning, COUNT(*) as booking_count')
            ->groupBy('earnings.currency')
            ->get();

        if ($result->isEmpty()) {
            return 0;
        }

        $currencyService = app(CurrencyConversionService::class);
        $totalUSD = 0;
        $totalBookings = 0;

        foreach ($result as $item) {
            $usdAmount = $currencyService->convertToUSD([$item->currency => $item->average_earning]);
            $totalUSD += $usdAmount * $item->booking_count;
            $totalBookings += $item->booking_count;
        }

        return $totalBookings > 0 ? $totalUSD / $totalBookings : 0;
    }

    protected function getChartData($startDate, $endDate): array
    {
        $dailyData = Earning::query()
            ->whereNotNull('earnings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->partner->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
            ->selectRaw('DATE(bookings.booking_at) as date, earnings.currency, COUNT(*) as bookings, SUM(earnings.amount) as total_earnings, AVG(earnings.amount) as avg_earning')
            ->groupBy('date', 'earnings.currency')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $totalBookings = $dayData->sum('bookings');
            $totalEarningsUSD = $currencyService->convertToUSD($dayData->pluck('total_earnings', 'currency')->toArray());

            $avgEarningUSD = 0;
            foreach ($dayData as $item) {
                $avgEarningUSD += $currencyService->convertToUSD([$item->currency => $item->avg_earning]) * $item->bookings;
            }
            $avgEarningUSD = $totalBookings > 0 ? $avgEarningUSD / $totalBookings : 0;

            return [
                'bookings' => $totalBookings,
                'earnings' => $totalEarningsUSD,
                'avg_booking_value' => $avgEarningUSD,
            ];
        });

        return [
            'bookings' => $chartData->pluck('bookings')->toArray(),
            'earnings' => $chartData->pluck('earnings')->toArray(),
            'avg_booking_value' => $chartData->pluck('avg_booking_value')->toArray(),
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

    protected function createEarningsStat(float $totalEarningsUSD, float $prevTotalEarningsUSD, array $currencyBreakdown): Stat
    {
        $stat = Stat::make('Earnings (converted to USD)', '$'.number_format($totalEarningsUSD, 2));

        $breakdownDescription = collect($currencyBreakdown)
            ->map(fn ($amount, $currency) => "$currency ".number_format($amount / 100, 2))
            ->implode(', ');

        $stat->description($breakdownDescription);

        if ($prevTotalEarningsUSD > 0) {
            $percentageChange = (($totalEarningsUSD - $prevTotalEarningsUSD) / $prevTotalEarningsUSD) * 100;
            $stat->color($percentageChange >= 0 ? 'success' : 'danger');
        }

        return $stat;
    }
}
