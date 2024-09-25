<?php

namespace App\Livewire\Concierge;

use App\Enums\EarningType;
use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Earning;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
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
        $currencyService = app(CurrencyConversionService::class);

        $conciergeEarningTypes = [
            EarningType::CONCIERGE->value,
            EarningType::CONCIERGE_REFERRAL_1->value,
            EarningType::CONCIERGE_REFERRAL_2->value,
        ];

        $earnings = Earning::query()
            ->select(
                'earnings.user_id',
                'concierges.id as concierge_id',
                DB::raw('SUM(earnings.amount) as total_earned'),
                'earnings.currency',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                DB::raw('COUNT(DISTINCT CASE WHEN earnings.type = "concierge" THEN bookings.id END) as direct_booking_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN earnings.type IN ("concierge_referral_1", "concierge_referral_2") THEN bookings.id END) as referral_booking_count')
            )
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('concierges', 'concierges.user_id', '=', 'earnings.user_id')
            ->whereIn('earnings.type', $conciergeEarningTypes)
            ->whereNotNull('bookings.confirmed_at')
            ->when($this->startDate && $this->endDate, fn (Builder $query) => $query->whereBetween('bookings.booking_at', [$this->startDate, $this->endDate]))
            ->groupBy('earnings.user_id', 'concierges.id', 'earnings.currency')
            ->get();

        $conciergeTotals = $earnings->groupBy('user_id')
            ->map(function (Collection $userEarnings) use ($currencyService) {
                $totalUSD = $userEarnings->sum(fn ($earning) => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]));

                return [
                    'user_id' => $userEarnings->first()->user_id,
                    'concierge_id' => $userEarnings->first()->concierge_id,
                    'user_name' => $userEarnings->first()->user_name,
                    'total_usd' => $totalUSD,
                    'direct_booking_count' => $userEarnings->sum('direct_booking_count'),
                    'referral_booking_count' => $userEarnings->sum('referral_booking_count'),
                    'total_booking_count' => $userEarnings->sum('direct_booking_count') + $userEarnings->sum('referral_booking_count'),
                    'earnings_breakdown' => $userEarnings->map(fn ($earning) => [
                        'amount' => $earning->total_earned,
                        'currency' => $earning->currency,
                        'usd_equivalent' => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]),
                    ])->toArray(),
                ];
            })
            ->sortByDesc('total_usd')
            ->values()
            ->take($this->limit)
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;

                return $item;
            });

        return collect($conciergeTotals);
    }

    public function viewConcierge($partnerId): void
    {
        $this->redirect(ViewConcierge::getUrl(['record' => $partnerId]));
    }
}
