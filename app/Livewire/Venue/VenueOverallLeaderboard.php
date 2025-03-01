<?php

namespace App\Livewire\Venue;

use App\Models\Earning;
use App\Models\Venue;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class VenueOverallLeaderboard extends Widget
{
    protected static string $view = 'livewire.venue.venue-overall-leaderboard';

    protected static ?string $pollingInterval = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public int $limit = 10;

    public ?string $selectedRegion = null;

    public ?Venue $venue = null;

    public function mount(?Venue $venue = null): void
    {
        $this->venue = $venue;
        if ($venue) {
            $this->selectedRegion = $venue->region;
        }
    }

    protected function getUserTimezone(): string
    {
        return auth()->user()?->timezone ?? config('app.default_timezone');
    }

    public function getLeaderboardData(): Collection
    {
        $userTimezone = $this->getUserTimezone();
        $startDateString = $this->startDate
            ? $this->startDate->format('Y-m-d')
            : now($userTimezone)->subDays(30)->format('Y-m-d');
        $endDateString = $this->endDate
            ? $this->endDate->format('Y-m-d')
            : now($userTimezone)->format('Y-m-d');

        // Parse the dates as in the user's timezone and then convert to UTC
        $tempStartDate = Carbon::parse($startDateString, $userTimezone)
            ->startOfDay()
            ->setTimezone('UTC');
        $tempEndDate = Carbon::parse($endDateString, $userTimezone)
            ->endOfDay()
            ->setTimezone('UTC');

        $cacheKey = "venue_leaderboard_{$tempStartDate->toDateTimeString()}_{$tempEndDate->toDateTimeString()}_{$this->selectedRegion}";

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(config('app.widget_cache_timeout_minutes')),
            function () use ($tempStartDate, $tempEndDate) {
                $currencyService = app(CurrencyConversionService::class);

                $query = Earning::query()
                    ->select([
                        'earnings.user_id',
                        'venues.id as venue_id',
                        DB::raw('SUM(earnings.amount) as total_earned'),
                        'venues.name as venue_name',
                        'venues.region',
                        'earnings.currency',
                        DB::raw('COUNT(DISTINCT earnings.booking_id) as booking_count'),
                    ])
                    ->join('venues', 'venues.user_id', '=', 'earnings.user_id')
                    ->join('bookings', function (Builder $join) use ($tempStartDate, $tempEndDate) {
                        $join->on('earnings.booking_id', '=', 'bookings.id')
                            ->whereNotNull('bookings.confirmed_at')
                            ->whereBetween('bookings.confirmed_at', [$tempStartDate, $tempEndDate]);
                    });

                if ($this->selectedRegion) {
                    $query->where('venues.region', $this->selectedRegion);
                }

                $earnings = $query
                    ->groupBy('earnings.user_id', 'venues.id', 'venues.region', 'earnings.currency')
                    ->orderByDesc('total_earned')
                    ->limit($this->limit)
                    ->get();

                return $earnings->filter(fn ($row) => $row->total_earned > 0)
                    ->map(function ($earning) use ($currencyService) {
                        $totalUSD = $currencyService->convertToUSD([$earning->currency => $earning->total_earned]);

                        return [
                            'user_id' => $earning->user_id,
                            'venue_id' => $earning->venue_id,
                            'venue_name' => $earning->venue_name,
                            'booking_count' => $earning->booking_count,
                            'total_earned' => $earning->total_earned,
                            'currency' => $earning->currency,
                            'currency_symbol' => $this->getCurrencySymbol($earning->currency),
                            'total_usd' => $totalUSD,
                            'region' => $earning->region,
                        ];
                    });
            }
        );
    }

    public function getRegions(): Collection
    {
        return Venue::query()->distinct()
            ->pluck('region')
            ->map(fn ($region) => [
                'value' => $region,
                'label' => $this->formatRegionName($region),
            ]);
    }

    public function updatedSelectedRegion(): void
    {
        $this->dispatch('leaderboardUpdated');
    }

    public function showRegionFilter(): bool
    {
        return auth()->user()->hasActiveRole('super_admin') && count(config('app.active_regions')) > 1;
    }

    public function viewVenue($venueId): void
    {
        $this->redirect(route('filament.admin.resources.venues.view', ['record' => $venueId]));
    }

    private function getCurrencySymbol(string $currencyCode): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            // Add more currency symbols as needed
        ];

        return $symbols[$currencyCode] ?? $currencyCode;
    }

    private function formatRegionName(string $region): string
    {
        return ucwords(str_replace('_', ' ', $region));
    }
}
