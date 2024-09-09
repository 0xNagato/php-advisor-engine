<?php

namespace App\Livewire\Venue;

use App\Models\Earning;
use App\Models\Venue;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class VenueOverallLeaderboard extends Widget
{
    protected static string $view = 'livewire.venue.venue-overall-leaderboard';

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public int $limit = 10;

    public ?string $selectedRegion = null;

    public ?Venue $venue = null;

    public function mount(?Venue $venue = null)
    {
        $this->venue = $venue;
        if ($venue) {
            $this->selectedRegion = $venue->region;
        }
    }

    public function getLeaderboardData(): Collection
    {
        $currencyService = app(CurrencyConversionService::class);

        $startDate = $this->startDate ?? now()->subDays(30);
        $endDate = $this->endDate ?? now();

        $query = Earning::query()
            ->select([
                'earnings.user_id',
                'venues.id as venue_id',
                DB::raw('SUM(CASE WHEN earnings.type = "venue" THEN earnings.amount ELSE 0 END) as total_earned'),
                'venues.name as venue_name',
                'venues.region',
                'earnings.currency',
                DB::raw('COUNT(DISTINCT bookings.id) as booking_count'),
            ])
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('venues', 'venues.user_id', '=', 'earnings.user_id')
            ->whereBetween('bookings.booking_at', [$startDate, $endDate]);

        if ($this->selectedRegion) {
            $query->where('venues.region', $this->selectedRegion);
        }

        $earnings = $query
            ->groupBy('earnings.user_id', 'venues.id', 'venues.region', 'earnings.currency')
            ->orderByDesc('total_earned')
            ->limit($this->limit)
            ->get();

        $venueTotals = $earnings->map(function ($earning, $index) use ($currencyService) {
            $totalUSD = $currencyService->convertToUSD([$earning->currency => $earning->total_earned]);

            return [
                'rank' => $index + 1,
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

        return $venueTotals;
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

    public function updatedSelectedRegion()
    {
        $this->dispatch('leaderboardUpdated');
    }

    public function showRegionFilter(): bool
    {
        return auth()->user()->hasRole('super_admin');
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
