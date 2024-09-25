<?php

namespace App\Livewire\Partner;

use App\Models\Earning;
use App\Models\Partner;
use Filament\Widgets\Widget;

class PartnerStats extends Widget
{
    protected static string $view = 'livewire.partner.partner-stats';

    protected static ?string $pollingInterval = null;

    public ?Partner $partner = null;

    public array $stats;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $partnerEarningsQuery = Earning::query()->where('user_id', $this->partner->user_id)
            ->whereIn('type', ['partner_concierge', 'partner_venue'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        $numberOfBookings = $partnerEarningsQuery->count();

        $partnerEarnings = $partnerEarningsQuery->selectRaw('currency, SUM(amount) as total_earnings')
            ->groupBy('currency')
            ->get();

        // Update the stats object
        $this->stats = [
            'currentEarningsByCurrency' => $partnerEarnings,
            'numberOfBookings' => $numberOfBookings,
        ];
    }
}
