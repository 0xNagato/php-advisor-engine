<?php

namespace App\Livewire;

use App\Models\Concierge;
use App\Models\Earning;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class ConciergeOverview extends BaseWidget
{
    public ?Concierge $concierge = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->subDays(30);
        $endDate = $this->endDate ?? now();

        $earnings = $this->getEarnings($startDate, $endDate);
        $prevEarnings = $this->getEarnings($startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate);
        $chartData = $this->getChartData($startDate, $endDate);

        $currencyService = app(CurrencyConversionService::class);
        $totalEarningsUSD = $currencyService->convertToUSD($earnings['earnings']);
        $prevTotalEarningsUSD = $currencyService->convertToUSD($prevEarnings['earnings']);

        $avgBookingValue = $this->getAverageBookingValue($startDate, $endDate);
        $prevAvgBookingValue = $this->getAverageBookingValue($startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate);

        return [
            $this->createStat('Direct Bookings', $earnings['number_of_direct_bookings'], null, $prevEarnings['number_of_direct_bookings'])
                ->chart($chartData['direct_bookings'])
                ->color('success'),
            $this->createStat('Referral Bookings', $earnings['number_of_referral_bookings'], null, $prevEarnings['number_of_referral_bookings'])
                ->chart($chartData['referral_bookings'])
                ->color('info'),
            $this->createEarningsStat($totalEarningsUSD, $prevTotalEarningsUSD, $earnings['earnings'])
                ->chart($chartData['earnings'])
                ->color('success'),
            $this->createStat('Avg. Earning per Direct Booking', $avgBookingValue, 'USD', $prevAvgBookingValue)
                ->chart($chartData['avg_booking_value'])
                ->color('info'),
        ];
    }

    protected function getEarnings($startDate, $endDate): array
    {
        $earnings = Earning::query()
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->concierge->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('earnings.type', ['concierge', 'concierge_referral_1', 'concierge_referral_2'])
            ->select(
                DB::raw('COUNT(DISTINCT CASE WHEN earnings.type = "concierge" THEN bookings.id END) as number_of_direct_bookings'),
                DB::raw('COUNT(DISTINCT CASE WHEN earnings.type IN ("concierge_referral_1", "concierge_referral_2") THEN bookings.id END) as number_of_referral_bookings'),
                DB::raw('SUM(earnings.amount) as total_earnings'),
                'earnings.currency'
            )
            ->groupBy('earnings.currency')
            ->get();

        return [
            'number_of_direct_bookings' => $earnings->sum('number_of_direct_bookings'),
            'number_of_referral_bookings' => $earnings->sum('number_of_referral_bookings'),
            'earnings' => $earnings->pluck('total_earnings', 'currency')->toArray(),
        ];
    }

    protected function getAverageBookingValue($startDate, $endDate): float
    {
        $result = Earning::query()
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->concierge->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->where('earnings.type', 'concierge') // Only consider direct bookings
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
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where('earnings.user_id', $this->concierge->user_id)
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('earnings.type', ['concierge', 'concierge_referral_1', 'concierge_referral_2'])
            ->selectRaw('DATE(bookings.booking_at) as date, earnings.currency, earnings.type, COUNT(*) as bookings, SUM(earnings.amount) as total_earnings, AVG(earnings.amount) as avg_earning')
            ->groupBy('date', 'earnings.currency', 'earnings.type')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $directBookings = $dayData->where('type', 'concierge')->sum('bookings');
            $referralBookings = $dayData->whereIn('type', ['concierge_referral_1', 'concierge_referral_2'])->sum('bookings');
            $totalEarningsUSD = $currencyService->convertToUSD($dayData->pluck('total_earnings', 'currency')->toArray());

            $avgDirectEarningUSD = 0;
            $directBookingsData = $dayData->where('type', 'concierge');
            foreach ($directBookingsData as $item) {
                $avgDirectEarningUSD += $currencyService->convertToUSD([$item->currency => $item->avg_earning]) * $item->bookings;
            }
            $avgDirectEarningUSD = $directBookings > 0 ? $avgDirectEarningUSD / $directBookings : 0;

            return [
                'direct_bookings' => $directBookings,
                'referral_bookings' => $referralBookings,
                'earnings' => $totalEarningsUSD,
                'avg_booking_value' => $avgDirectEarningUSD,
            ];
        });

        return [
            'direct_bookings' => $chartData->pluck('direct_bookings')->toArray(),
            'referral_bookings' => $chartData->pluck('referral_bookings')->toArray(),
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
            // Add more currencies as needed
            default => $currency ?? '',
        };
    }

    protected function createEarningsStat(float $totalEarningsUSD, float $prevTotalEarningsUSD, array $currencyBreakdown): Stat
    {
        $stat = Stat::make('Earnings', '$'.number_format($totalEarningsUSD, 2));

        $breakdownDescription = collect($currencyBreakdown)
            ->map(function ($amount, $currency) {
                $symbol = $this->getCurrencySymbol($currency);

                return $symbol.number_format($amount / 100, 2);
            })
            ->implode(', ');

        $stat->description($breakdownDescription);

        if ($prevTotalEarningsUSD > 0) {
            $percentageChange = (($totalEarningsUSD - $prevTotalEarningsUSD) / $prevTotalEarningsUSD) * 100;
            $stat->color($percentageChange >= 0 ? 'success' : 'danger');
        }

        return $stat;
    }
}
