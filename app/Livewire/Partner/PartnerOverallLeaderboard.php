<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Models\Earning;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Database\Query\Builder;
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
        $cacheKey = "partner_leaderboard_{$this->startDate}_{$this->endDate}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $currencyService = app(CurrencyConversionService::class);

            $earnings = Earning::query()
                ->select(
                    'earnings.user_id',
                    'partners.id as partner_id',
                    DB::raw('SUM(earnings.amount) as total_earned'),
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                    'earnings.currency',
                    DB::raw('COUNT(DISTINCT earnings.booking_id) as booking_count')
                )
                ->join('partners', 'partners.user_id', '=', 'earnings.user_id')
                ->join('users', 'users.id', '=', 'earnings.user_id')
                ->join('bookings', function (Builder $join) {
                    $join->on('earnings.booking_id', '=', 'bookings.id')
                        ->whereNotNull('bookings.confirmed_at')
                        ->whereBetween('bookings.booking_at', [$this->startDate, $this->endDate]);
                })
                ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
                ->groupBy('earnings.user_id', 'partners.id', 'earnings.currency')
                ->get();

            $partnerTotals = $earnings->groupBy('user_id')->map(function ($partnerEarnings) use ($currencyService) {
                $totalUSD = $partnerEarnings->sum(fn ($earning) => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]));

                return [
                    'user_id' => $partnerEarnings->first()->user_id,
                    'partner_id' => $partnerEarnings->first()->partner_id,
                    'user_name' => $partnerEarnings->first()->user_name,
                    'total_usd' => $totalUSD,
                    'booking_count' => $partnerEarnings->sum('booking_count'),
                    'earnings_breakdown' => $partnerEarnings->map(fn ($earning) => [
                        'amount' => $earning->total_earned,
                        'currency' => $earning->currency,
                        'usd_equivalent' => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]),
                    ])->toArray(),
                ];
            })->sortByDesc('total_usd')->take($this->limit)->values();

            return collect($partnerTotals);
        });
    }

    public function viewPartner($partnerId): void
    {
        $this->redirect(ViewPartner::getUrl(['record' => $partnerId]));
    }
}
