<?php

namespace App\Livewire\Concierge;

use App\Enums\BookingStatus;
use App\Enums\EarningType;
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

    public function mount(): void {}

    /**
     * Returns the authenticated user's timezone or the default.
     */
    protected function getUserTimezone(): string
    {
        return auth()->user()?->timezone ?? config('app.default_timezone');
    }

    public function getLeaderboardData(): Collection
    {
        $userTimezone = $this->getUserTimezone();

        // Format the date as a Y-m-d string using the user's timezone.
        $startDateString = $this->startDate
            ? $this->startDate->format('Y-m-d')
            : now($userTimezone)->subDays(30)->format('Y-m-d');
        $endDateString = $this->endDate
            ? $this->endDate->format('Y-m-d')
            : now($userTimezone)->format('Y-m-d');

        // Parse the dates using the user's timezone, set the boundaries, then convert to UTC.
        $tempStartDate = Carbon::parse($startDateString, $userTimezone)
            ->startOfDay()
            ->setTimezone('UTC');
        $tempEndDate = Carbon::parse($endDateString, $userTimezone)
            ->endOfDay()
            ->setTimezone('UTC');

        $cacheKey = "concierge_leaderboard_{$tempStartDate->toDateTimeString()}_{$tempEndDate->toDateTimeString()}";

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(config('app.widget_cache_timeout_minutes')),
            function () use ($tempStartDate, $tempEndDate) {
                $currencyService = app(CurrencyConversionService::class);

                $conciergeEarningTypes = [
                    EarningType::CONCIERGE,
                    EarningType::CONCIERGE_REFERRAL_1,
                    EarningType::CONCIERGE_REFERRAL_2,
                    EarningType::CONCIERGE_BOUNTY,
                ];

                $earnings = Earning::query()
                    ->select(
                        'earnings.user_id',
                        'concierges.id as concierge_id',
                        DB::raw('SUM(earnings.amount) as total_earned'),
                        'earnings.currency',
                        DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                        DB::raw("COUNT(DISTINCT CASE WHEN earnings.type IN ('concierge', 'concierge_bounty') THEN earnings.booking_id END) as direct_booking_count"),
                        DB::raw("COUNT(DISTINCT CASE WHEN earnings.type IN ('concierge_referral_1', 'concierge_referral_2') THEN earnings.booking_id END) as referral_booking_count")
                    )
                    ->join('concierges', 'concierges.user_id', '=', 'earnings.user_id')
                    ->join('users', 'users.id', '=', 'earnings.user_id')
                    ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
                    ->whereNotNull('bookings.confirmed_at')
                    ->whereBetween('bookings.confirmed_at', [$tempStartDate, $tempEndDate])
                    ->whereIn('bookings.status', [
                        BookingStatus::CONFIRMED,
                        BookingStatus::VENUE_CONFIRMED,
                        BookingStatus::PARTIALLY_REFUNDED,
                        BookingStatus::NO_SHOW,
                        BookingStatus::CANCELLED,
                    ])
                    ->whereIn('earnings.type', $conciergeEarningTypes)
                    ->groupBy('earnings.user_id', 'concierges.id', 'earnings.currency', 'users.first_name', 'users.last_name')
                    ->get()
                    ->filter(fn ($row) => $row->total_earned > 0);

                $conciergeTotals = $earnings->groupBy('user_id')->map(function ($conciergeEarnings) use ($currencyService) {
                    $totalUSD = $conciergeEarnings->sum(fn ($earning) => $currencyService->convertToUSD([$earning->currency => $earning->total_earned])
                    );

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
            }
        );
    }

    public function viewConcierge($conciergeId): void
    {
        $this->redirect(ViewConcierge::getUrl(['record' => $conciergeId]));
    }
}
