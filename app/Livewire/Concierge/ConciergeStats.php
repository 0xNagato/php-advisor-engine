<?php

namespace App\Livewire\Concierge;

use App\Models\Concierge;
use App\Models\Earning;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class ConciergeStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.concierge.concierge-stats';

    protected static bool $isLazy = false;

    public ?Concierge $concierge = null;

    public array $stats;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $conciergeEarningsQuery = Earning::where('user_id', $this->concierge->user_id)
            ->whereIn('type', ['concierge', 'concierge_referral_1', 'concierge_referral_2', 'concierge_bounty'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        $numberOfBookings = $conciergeEarningsQuery->count();

        $conciergeEarnings = $conciergeEarningsQuery->selectRaw('currency, SUM(amount) as total_earnings')
            ->groupBy('currency')
            ->get();

        $this->stats = [
            'currentEarningsByCurrency' => $conciergeEarnings,
            'numberOfBookings' => $numberOfBookings,
        ];
    }
}
