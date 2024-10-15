<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Models\Partner;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
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

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tempStartDate, $tempEndDate) {
            $currencyService = app(CurrencyConversionService::class);

            $earnings = Partner::query()
                ->join('users', 'users.id', '=', 'partners.user_id')
                ->join('bookings', function ($join) use ($tempStartDate, $tempEndDate) {
                    $join->on(function ($join) {
                        $join->whereColumn('bookings.partner_concierge_id', 'partners.id')
                            ->orWhereColumn('bookings.partner_venue_id', 'partners.id');
                    })
                    ->whereNotNull('bookings.confirmed_at')
                    ->whereBetween('bookings.confirmed_at', [$tempStartDate, $tempEndDate]);
                })
                ->join('earnings', function ($join) {
                    $join->on('earnings.booking_id', '=', 'bookings.id')
                        ->whereIn('earnings.type', ['partner_concierge', 'partner_venue']);
                })
                ->select(
                    'partners.id as partner_id',
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                    'earnings.currency',
                    DB::raw('SUM(earnings.amount) as total_earned'),
                    DB::raw('COUNT(DISTINCT bookings.id) as booking_count')
                )
                ->groupBy('partners.id', 'earnings.currency', 'users.first_name', 'users.last_name')
                ->get();

            $partnerTotals = $earnings->groupBy('partner_id')->map(function ($partnerEarnings) use ($currencyService) {
                $totalUSD = $partnerEarnings->sum(function ($earning) use ($currencyService) {
                    return $currencyService->convertToUSD([$earning->currency => $earning->total_earned]);
                });

                return [
                    'partner_id' => $partnerEarnings->first()->partner_id,
                    'user_name' => $partnerEarnings->first()->user_name,
                    'total_usd' => $totalUSD,
                    'booking_count' => $partnerEarnings->sum('booking_count'),
                    'earnings_breakdown' => $partnerEarnings->map(function ($earning) use ($currencyService) {
                        return [
                            'amount' => $earning->total_earned,
                            'currency' => $earning->currency,
                            'usd_equivalent' => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]),
                        ];
                    })->toArray(),
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
