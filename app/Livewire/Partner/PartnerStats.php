<?php

namespace App\Livewire\Partner;

use App\Models\Earning;
use App\Models\Partner;
use Filament\Widgets\Widget;

class PartnerStats extends Widget
{
    protected static string $view = 'livewire.partner.partner-stats';

    protected static bool $isLazy = false;

    public ?Partner $partner;

    public array $stats;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $partnerEarningsQuery = Earning::where('user_id', $this->partner->user_id)
            ->whereIn('type', ['partner_concierge', 'partner_restaurant'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        $partnerEarnings = $partnerEarningsQuery->selectRaw('currency, SUM(amount) as total_earnings')
            ->groupBy('currency')
            ->get();

        $numberOfBookings = $partnerEarningsQuery->count();

        // Update the stats object
        $this->stats = [
            'currentEarningsByCurrency' => $partnerEarnings,
            'numberOfBookings' => $numberOfBookings,
        ];
    }
}
