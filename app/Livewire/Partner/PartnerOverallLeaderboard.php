<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class PartnerOverallLeaderboard extends Widget
{
    protected static string $view = 'livewire.partner-overall-leaderboard';

    protected static ?string $pollingInterval = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public int $limit = 10;

    public function getLeaderboardData(): Collection
    {
        $tempStartDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $tempEndDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();

        $cacheKey = "partner_leaderboard_{$tempStartDate->toDateString()}_{$tempEndDate->toDateString()}";

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(config('app.widget_cache_timeout_minutes')),
            function () use ($tempStartDate, $tempEndDate): Collection {
                $currencyService = app(CurrencyConversionService::class);

                $conciergeEarnings = $this->getEarningsQuery($tempStartDate, $tempEndDate);
                $venueEarnings = $this->getEarningsQuery($tempStartDate, $tempEndDate);

                $allEarnings = $conciergeEarnings->union($venueEarnings)->get();

                $partnerTotals = $allEarnings->groupBy('partner_id')->map(function (Collection $partnerEarnings) use ($currencyService): array {
                    $totalEarned = $partnerEarnings->sum('total_amount');
                    $totalUSD = $currencyService->convertToUSD([$partnerEarnings->first()->currency => $totalEarned]);

                    return [
                        'partner_id' => $partnerEarnings->first()->partner_id,
                        'user_id' => $partnerEarnings->first()->user_id,
                        'user_name' => $partnerEarnings->first()->user_name,
                        'total_usd' => $totalUSD,
                        'booking_count' => $partnerEarnings->sum('booking_count'),
                        'earnings_breakdown' => [
                            [
                                'amount' => $totalEarned,
                                'currency' => $partnerEarnings->first()->currency,
                                'usd_equivalent' => $totalUSD,
                            ],
                        ],
                    ];
                })->sortByDesc('total_usd')->take($this->limit)->values();

                return collect($partnerTotals);
            }
        );
    }

    private function getEarningsQuery(Carbon $startDate, Carbon $endDate): QueryBuilder
    {
        return DB::table('partners')
            ->join('users', 'users.id', '=', 'partners.user_id')
            ->join('bookings', function (Builder $join) {
                $join->on('bookings.partner_concierge_id', '=', 'partners.id')
                    ->orOn('bookings.partner_venue_id', '=', 'partners.id');
            })
            ->join('earnings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
            ->select(
                'partners.id as partner_id',
                'partners.user_id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                'earnings.currency',
                DB::raw('SUM(CASE
                    WHEN bookings.partner_concierge_id = partners.id AND bookings.partner_venue_id = partners.id THEN earnings.amount
                    ELSE earnings.amount / 2
                END) as total_amount'),
                DB::raw('COUNT(DISTINCT bookings.id) as booking_count')
            )
            ->groupBy('partners.id', 'partners.user_id', 'users.first_name', 'users.last_name', 'earnings.currency');
    }

    public function viewPartner(int $partnerId): void
    {
        $this->redirect(ViewPartner::getUrl(['record' => $partnerId]));
    }
}
