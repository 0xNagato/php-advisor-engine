<?php

namespace App\Livewire\Concierge;

use App\Data\ConciergeStatData;
use App\Models\Concierge;
use App\Models\Earning;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class ConciergeStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.concierge.concierge-stats';

    protected static bool $isLazy = false;

    public ?Concierge $concierge;

    public ConciergeStatData $stats;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Get all earnings related to the concierge
        $conciergeEarningsQuery = Earning::where('user_id', $this->concierge->user_id)
            ->whereIn('type', ['concierge', 'concierge_referral_1', 'concierge_referral_2'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        // Calculate concierge earnings as the sum of amount
        $conciergeEarnings = $conciergeEarningsQuery->sum('amount');

        $numberOfBookings = $conciergeEarningsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevConciergeEarningsQuery = Earning::where('user_id', $this->concierge->user_id)
            ->whereIn('type', ['concierge'])
            ->whereBetween('confirmed_at', [$prevStartDate, $prevEndDate]);

        // Calculate previous concierge earnings as the sum of amount
        $prevConciergeEarnings = $prevConciergeEarningsQuery->sum('amount');

        $prevNumberOfBookings = $prevConciergeEarningsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new ConciergeStatData([
            'current' => [
                'original_earnings' => $conciergeEarnings,
                'concierge_earnings' => $conciergeEarnings,
                'charity_earnings' => 0, // Assuming charity earnings are not applicable here
                'number_of_bookings' => $numberOfBookings,
                'concierge_contribution' => $conciergeEarnings,
            ],
            'previous' => [
                'original_earnings' => $prevConciergeEarnings,
                'concierge_earnings' => $prevConciergeEarnings,
                'charity_earnings' => 0, // Assuming charity earnings are not applicable here
                'number_of_bookings' => $prevNumberOfBookings,
                'concierge_contribution' => $prevConciergeEarnings,
            ],
            'difference' => [
                'original_earnings' => $conciergeEarnings - $prevConciergeEarnings,
                'original_earnings_up' => $conciergeEarnings >= $prevConciergeEarnings,
                'concierge_earnings' => $conciergeEarnings - $prevConciergeEarnings,
                'concierge_earnings_up' => $conciergeEarnings >= $prevConciergeEarnings,
                'charity_earnings' => 0, // Assuming charity earnings are not applicable here
                'charity_earnings_up' => false, // Assuming charity earnings are not applicable here
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
                'concierge_contribution' => $conciergeEarnings - $prevConciergeEarnings,
                'concierge_contribution_up' => $conciergeEarnings >= $prevConciergeEarnings,
            ],
            'formatted' => [
                'original_earnings' => $this->formatNumber($conciergeEarnings),
                'concierge_earnings' => $this->formatNumber($conciergeEarnings),
                'charity_earnings' => $this->formatNumber(0), // Assuming charity earnings are not applicable here
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'concierge_contribution' => $this->formatNumber($conciergeEarnings),
                'difference' => [
                    'original_earnings' => $this->formatNumber($conciergeEarnings - $prevConciergeEarnings),
                    'concierge_earnings' => $this->formatNumber($conciergeEarnings - $prevConciergeEarnings),
                    'charity_earnings' => $this->formatNumber(0), // Assuming charity earnings are not applicable here
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                    'concierge_contribution' => $this->formatNumber($conciergeEarnings - $prevConciergeEarnings),
                ],
            ],
        ]);
    }

    private function formatNumber($number): string
    {
        $number = round($number / 100, 2); // Convert to dollars from cents and round to nearest two decimal places.
        if ($number >= 1000) {
            return '$'.number_format($number / 1000, 2).'k'; // Convert to k if number is greater than or equal to 1000 and keep one decimal place.
        }

        return money($number, 'USD', true);
    }
}
