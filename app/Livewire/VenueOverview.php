<?php

namespace App\Livewire;

use App\Enums\EarningType;
use App\Models\Earning;
use App\Models\Venue;
use App\Services\CurrencyConversionService;
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
        $currencySymbol = $this->venue->inRegion->currency_symbol;

        $earnings = $this->getEarnings($startDate, $endDate);
        $prevEarnings = $this->getEarnings($startDate->copy()->subDays($startDate->diffInDays($endDate)), $startDate);
        $chartData = $this->getChartData($startDate, $endDate);

        $venueEarnings = $this->sumScaledValues($earnings['venue_earnings']);
        $venuePaid = $this->sumScaledValues($earnings['venue_paid']);
        $totalEarnings = $venueEarnings - abs($venuePaid);

        $prevVenueEarnings = $this->sumScaledValues($prevEarnings['venue_earnings']);
        $prevVenuePaid = $this->sumScaledValues($prevEarnings['venue_paid']);
        $prevTotalEarnings = $prevVenueEarnings - abs($prevVenuePaid);

        return [
            $this->createStat('Total Bookings', $earnings['total_bookings'], null, $prevEarnings['total_bookings'])
                ->chart($chartData['total_bookings'])
                ->color('success'),
            $this->createStat('Prime Bookings', $earnings['prime_bookings'], null, $prevEarnings['prime_bookings'])
                ->chart($chartData['prime_bookings'])
                ->color('primary'),
            $this->createStat('Incentivised Bookings', $earnings['incentivised_bookings'], null,
                $prevEarnings['incentivised_bookings'])
                ->chart($chartData['incentivised_bookings'])
                ->color('warning'),
            $this->createStat('Total Earnings', $totalEarnings, $currencySymbol, $prevTotalEarnings)
                ->chart($chartData['total_earnings'])
                ->color('success'),
            $this->createStat('Prime Earnings', $venueEarnings, $currencySymbol, $prevVenueEarnings)
                ->chart($chartData['prime_earnings'])
                ->color('primary'),
            $this->createStat('Incentivised Cost', $venuePaid, $currencySymbol, $prevVenuePaid)
                ->chart($chartData['incentivised_cost'])
                ->color('warning'),
        ];
    }

    protected function getEarnings(Carbon $startDate, Carbon $endDate): array
    {
        $earnings = Earning::query()
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
            ->where('schedule_templates.venue_id', $this->venue->id)
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(DISTINCT CASE WHEN bookings.is_prime = true THEN bookings.id END) as prime_bookings'),
                DB::raw('COUNT(DISTINCT CASE WHEN bookings.is_prime = false THEN bookings.id END) as incentivised_bookings'),
                DB::raw("SUM(CASE WHEN earnings.type IN ('".EarningType::VENUE->value."', '".EarningType::REFUND->value."') THEN earnings.amount ELSE 0 END) as venue_earnings"),
                DB::raw("SUM(CASE WHEN earnings.type = 'venue_paid' THEN earnings.amount ELSE 0 END) as venue_paid"),
                'earnings.currency'
            )
            ->groupBy('earnings.currency')
            ->get();

        return [
            'total_bookings' => $earnings->sum('prime_bookings') + $earnings->sum('incentivised_bookings'),
            'prime_bookings' => $earnings->sum('prime_bookings'),
            'incentivised_bookings' => $earnings->sum('incentivised_bookings'),
            'venue_earnings' => $earnings->pluck('venue_earnings', 'currency')->toArray(),
            'venue_paid' => $earnings->pluck('venue_paid', 'currency')->toArray(),
        ];
    }

    protected function getChartData(Carbon $startDate, Carbon $endDate): array
    {
        $dailyData = Earning::query()
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
            ->where('schedule_templates.venue_id', $this->venue->id)
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->selectRaw("
                DATE(bookings.confirmed_at) as date,
                earnings.currency,
                COUNT(DISTINCT CASE WHEN bookings.is_prime = true THEN bookings.id END) as prime_bookings,
                COUNT(DISTINCT CASE WHEN bookings.is_prime = false THEN bookings.id END) as incentivised_bookings,
                SUM(CASE WHEN earnings.type IN ('".EarningType::VENUE->value."', '".EarningType::REFUND->value."') THEN earnings.amount ELSE 0 END) as prime_earnings,
                SUM(CASE WHEN earnings.type = 'venue_paid' THEN earnings.amount ELSE 0 END) as incentivised_cost
            ")
            ->groupBy('date', 'earnings.currency')
            ->orderBy('date')
            ->get();

        $currencyService = app(CurrencyConversionService::class);

        $chartData = $dailyData->groupBy('date')->map(function ($dayData) use ($currencyService) {
            $primeBookings = $dayData->sum('prime_bookings');
            $incentivisedBookings = $dayData->sum('incentivised_bookings');
            $primeEarningsUSD = $currencyService->convertToUSD($dayData->pluck(
                'prime_earnings',
                'currency'
            )->toArray());
            $incentivisedCostUSD = $currencyService->convertToUSD($dayData->pluck(
                'incentivised_cost',
                'currency'
            )->toArray());

            return [
                'total_bookings' => $primeBookings + $incentivisedBookings,
                'prime_bookings' => $primeBookings,
                'incentivised_bookings' => $incentivisedBookings,
                'total_earnings' => $primeEarningsUSD,
                'prime_earnings' => $primeEarningsUSD,
                'incentivised_cost' => $incentivisedCostUSD,
            ];
        });

        return [
            'total_bookings' => $chartData->pluck('total_bookings')->toArray(),
            'prime_bookings' => $chartData->pluck('prime_bookings')->toArray(),
            'incentivised_bookings' => $chartData->pluck('incentivised_bookings')->toArray(),
            'total_earnings' => $chartData->pluck('total_earnings')->toArray(),
            'prime_earnings' => $chartData->pluck('prime_earnings')->toArray(),
            'incentivised_cost' => $chartData->pluck('incentivised_cost')->toArray(),
        ];
    }

    protected function createStat(
        string $label,
        float $value,
        ?string $currencySymbol = null,
        float $previousValue = 0
    ): Stat {
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

    /**
     * Sums an array of monetary values in subunits and scales them to units.
     *
     * @param  array  $values  Key-value array of monetary amounts grouped by currency.
     * @return float Aggregated amount scaled to units (e.g., dollars).
     */
    protected function sumScaledValues(array $values): float
    {
        $total = 0;

        foreach ($values as $amount) {
            $total += $amount / 100;
        }

        return $total;
    }
}
