<?php

namespace App\Livewire\Concierge;

use App\Enums\EarningType;
use App\Models\Concierge;
use App\Models\Earning;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Database\Query\Builder;

class ConciergeReferralStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.concierge-referral-stats';

    protected static ?string $pollingInterval = null;

    public Concierge $concierge;

    public ?array $stats = [
        'earnings' => 0,
        'referrals' => 0,
    ];

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function mount(): void
    {
        $userId = auth()->id();

        $startDate = Carbon::parse($this->filters['startDate'] ?? now()->subDays(30));
        $endDate = Carbon::parse($this->filters['endDate'] ?? now());

        $earningsQuery = Earning::query()
            ->whereIn('type', [EarningType::CONCIERGE_REFERRAL_1, EarningType::CONCIERGE_REFERRAL_2])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('booking', function (Builder $query) {
                $query->whereNotNull('confirmed_at')
                    ->whereNotIn('status', ['cancelled', 'refunded']);
            });

        $referralsQuery = Earning::query()
            ->whereIn('type', [EarningType::CONCIERGE_REFERRAL_1, EarningType::CONCIERGE_REFERRAL_2])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('booking', function (Builder $query) {
                $query->whereNotNull('confirmed_at')
                    ->whereNotIn('status', ['cancelled', 'refunded']);
            });

        // For referral earnings, always filter by the user who receives the earnings
        $earningsQuery->where('user_id', $userId);
        $referralsQuery->where('user_id', $userId);

        $this->stats['earnings'] = $earningsQuery->sum('amount');
        $this->stats['referrals'] = $referralsQuery->count();
    }
}
