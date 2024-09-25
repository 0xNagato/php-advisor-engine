<?php

namespace App\Livewire\Partner;

use App\Models\Earning;
use App\Models\Venue;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class VenueReferralStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.concierge-referral-stats';

    protected static ?string $pollingInterval = null;

    public Venue $venue;

    public ?array $stats = [
        'earnings' => 0,
        'earningsPrevious' => 0,
        'earningsDifference' => 0,
        'referrals' => 0,
        'referralsPrevious' => 0,
        'referralsDifference' => 0,
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

        $dateDiffInDays = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($dateDiffInDays);
        $previousEndDate = $endDate->copy()->subDays($dateDiffInDays);

        $earningsQuery = Earning::confirmed()
            ->whereIn('type', ['partner_venue'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        $referralsQuery = Earning::confirmed()
            ->whereIn('type', ['partner_venue'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->venue->exists) {
            $earningsQuery->whereHas('booking', function ($query) {
                $query->where('concierge_id', $this->venue->id);
            });

            $referralsQuery->whereHas('booking', function ($query) {
                $query->where('concierge_id', $this->venue->id);
            });
        } else {
            $earningsQuery->where('user_id', $userId);
            $referralsQuery->where('user_id', $userId);
        }

        $this->stats['earnings'] = $earningsQuery->sum('amount');
        $this->stats['referrals'] = $referralsQuery->count();

        $this->stats['earningsPrevious'] = $earningsQuery->whereBetween('created_at', [$previousStartDate, $previousEndDate])->sum('amount');
        $this->stats['referralsPrevious'] = $referralsQuery->whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();

        $this->stats['earningsDifference'] = $this->stats['earnings'] - $this->stats['earningsPrevious'];
        $this->stats['referralsDifference'] = $this->stats['referrals'] - $this->stats['referralsPrevious'];
    }
}
