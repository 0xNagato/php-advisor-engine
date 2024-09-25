<?php

namespace App\Livewire\Concierge;

use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Earning;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class ConciergeOverallLeaderboard extends Widget
{
    protected static string $view = 'livewire.concierge-overall-leaderboard';

    protected static ?string $pollingInterval = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public int $limit = 10;

    public function getLeaderboardData(): Collection
    {
        $cacheKey = "concierge_leaderboard_{$this->startDate}_{$this->endDate}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $currencyService = app(CurrencyConversionService::class);

            $conciergeEarningTypes = ['concierge', 'concierge_referral_1', 'concierge_referral_2'];

            $earnings = Earning::query()
                ->select(
                    'earnings.user_id',
                    'concierges.id as concierge_id',
                    DB::raw('SUM(earnings.amount) as total_earned'),
                    'earnings.currency',
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                    DB::raw('COUNT(DISTINCT CASE WHEN earnings.type = "concierge" THEN earnings.booking_id END) as direct_booking_count'),
                    DB::raw('COUNT(DISTINCT CASE WHEN earnings.type IN ("concierge_referral_1", "concierge_referral_2") THEN earnings.booking_id END) as referral_booking_count')
                )
                ->join('concierges', 'concierges.user_id', '=', 'earnings.user_id')
                ->join('users', 'users.id', '=', 'earnings.user_id')
                ->join('bookings', function ($join) {
                    $join->on('earnings.booking_id', '=', 'bookings.id')
                        ->whereNotNull('bookings.confirmed_at')
                        ->whereBetween('bookings.booking_at', [$this->startDate, $this->endDate]);
                })
                ->whereIn('earnings.type', $conciergeEarningTypes)
                ->groupBy('earnings.user_id', 'concierges.id', 'earnings.currency')
                ->get();

            $conciergeTotals = $earnings->groupBy('user_id')->map(function ($conciergeEarnings) use ($currencyService) {
                $totalUSD = $conciergeEarnings->sum(fn ($earning) => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]));

                return [
                    'user_id' => $conciergeEarnings->first()->user_id,
                    'concierge_id' => $conciergeEarnings->first()->concierge_id,
                    'user_name' => $conciergeEarnings->first()->user_name,
                    'total_usd' => $totalUSD,
                    'direct_booking_count' => $conciergeEarnings->sum('direct_booking_count'),
                    'referral_booking_count' => $conciergeEarnings->sum('referral_booking_count'),
                    'earnings_breakdown' => $conciergeEarnings->map(fn ($earning) => [
                        'amount' => $earning->total_earned,
                        'currency' => $earning->currency,
                        'usd_equivalent' => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]),
                    ])->toArray(),
                ];
            })->sortByDesc('total_usd')->take($this->limit)->values();

            return collect($conciergeTotals);
        });
    }

    public function viewConcierge($conciergeId): void
    {
        $this->redirect(ViewConcierge::getUrl(['record' => $conciergeId]));
    }
}
