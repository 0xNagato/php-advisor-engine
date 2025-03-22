<?php

namespace App\Livewire;

use App\Enums\EarningType;
use App\Models\Earning;
use App\Models\Partner;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class PartnerOverview extends BaseWidget
{
    public ?Partner $partner = null;

    #[Reactive]
    public ?string $startDate = null;

    #[Reactive]
    public ?string $endDate = null;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $startDate = ($this->startDate ? Carbon::parse($this->startDate) : now()->subDays(30))->startOfDay();
        $endDate = ($this->endDate ? Carbon::parse($this->endDate) : now())->endOfDay();

        $earnings = $this->getEarnings($startDate, $endDate);
        $prevEarnings = $this->getEarnings($startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate);
        $chartData = $this->getChartData($startDate, $endDate);

        $currencyService = app(CurrencyConversionService::class);
        $totalEarningsUSD = $currencyService->convertToUSD($earnings['earnings']);
        $prevTotalEarningsUSD = $currencyService->convertToUSD($prevEarnings['earnings']);

        $avgBookingValue = $this->getAverageBookingValue($startDate, $endDate);
        $prevAvgBookingValue = $this->getAverageBookingValue($startDate->copy()->subDays($startDate->diffInDays($endDate)),
            $startDate);

        return [
            $this->createStat('Bookings', $earnings['number_of_bookings'], null, $prevEarnings['number_of_bookings'])
                ->chart($chartData['bookings'])
                ->color('success'),
            $this->createEarningsStat($totalEarningsUSD, $prevTotalEarningsUSD, $earnings['earnings'])
                ->chart($chartData['earnings'])
                ->color('success'),
            $this->createStat('Avg. Earning', $avgBookingValue, 'USD', $prevAvgBookingValue)
                ->chart($chartData['avg_booking_value'])
                ->color('info'),
        ];
    }

    protected function getEarnings($startDate, $endDate): array
    {
        $partnerEarnings = Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereIn('earnings.type', [
                EarningType::PARTNER_CONCIERGE,
                EarningType::PARTNER_VENUE,
                EarningType::REFUND,
            ])
            ->whereBetween('bookings.booking_at_utc', [$startDate, $endDate])
            ->where('earnings.user_id', $this->partner->user_id)
            ->where(function ($q) {
                $q->where('bookings.partner_concierge_id', $this->partner->id)
                    ->orWhere('bookings.partner_venue_id', $this->partner->id);
            })
            ->select(
                'bookings.id as booking_id',
                'earnings.currency',
                DB::raw('SUM(CASE
                    WHEN bookings.partner_concierge_id = '.$this->partner->id.' AND bookings.partner_venue_id = '.$this->partner->id.' THEN earnings.amount
                    ELSE earnings.amount / 2
                END) as total_amount')
            )
            ->groupBy('bookings.id', 'earnings.currency')
            ->get();

        $numberOfBookings = $partnerEarnings->count();
        $groupedEarnings = $partnerEarnings->groupBy('currency')
            ->map(fn ($group) => $group->sum('total_amount'));

        return [
            'number_of_bookings' => $numberOfBookings,
            'earnings' => $groupedEarnings->toArray(),
        ];
    }

    protected function getAverageBookingValue($startDate, $endDate): float
    {
        $result = Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereIn('earnings.type', [
                EarningType::PARTNER_CONCIERGE,
                EarningType::PARTNER_VENUE,
                EarningType::REFUND,
            ])
            ->whereBetween('bookings.booking_at_utc', [$startDate, $endDate])
            ->where('earnings.user_id', $this->partner->user_id)
            ->where(function ($q) {
                $q->where('bookings.partner_concierge_id', $this->partner->id)
                    ->orWhere('bookings.partner_venue_id', $this->partner->id);
            })
            ->selectRaw('earnings.currency, AVG(earnings.amount) as average_earning, COUNT(DISTINCT bookings.id) as booking_count')
            ->groupBy('earnings.currency')
            ->get();

        if ($result->isEmpty()) {
            return 0.0;
        }

        $currencyService = app(CurrencyConversionService::class);
        $totalUSD = 0.0;
        $totalBookings = 0;

        foreach ($result as $item) {
            $usdAmount = $currencyService->convertToUSD([$item->currency => $item->average_earning]);
            $totalUSD += $usdAmount * $item->booking_count;
            $totalBookings += $item->booking_count;
        }

        return $totalBookings > 0 ? $totalUSD / $totalBookings : 0.0;
    }

    protected function getChartData($startDate, $endDate): array
    {
        $dailyData = Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereIn('earnings.type', [
                EarningType::PARTNER_CONCIERGE,
                EarningType::PARTNER_VENUE,
                EarningType::REFUND,
            ])
            ->where('earnings.user_id', $this->partner->user_id)
            ->whereBetween('bookings.booking_at_utc', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('bookings.partner_concierge_id', $this->partner->id)
                    ->orWhere('bookings.partner_venue_id', $this->partner->id);
            })
            ->selectRaw('DATE(bookings.confirmed_at) as date, earnings.currency, COUNT(DISTINCT bookings.id) as bookings, SUM(earnings.amount) as total_earnings, AVG(earnings.amount) as avg_earning')
            ->groupBy('date', 'earnings.currency')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $totalBookings = $dayData->sum('bookings');
            $totalEarningsUSD = $currencyService->convertToUSD($dayData->pluck('total_earnings',
                'currency')->toArray());

            $avgEarningUSD = 0.0;
            foreach ($dayData as $item) {
                $usdAmount = $currencyService->convertToUSD([$item->currency => $item->avg_earning]);
                $avgEarningUSD += $usdAmount * $item->bookings;
            }
            $avgEarningUSD = $totalBookings > 0 ? $avgEarningUSD / $totalBookings : 0.0;

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

    protected function createEarningsStat(
        float $totalEarningsUSD,
        float $prevTotalEarningsUSD,
        array $currencyBreakdown
    ): Stat {
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
