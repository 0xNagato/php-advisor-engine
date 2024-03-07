<?php

namespace App\Livewire\Concierge;

use App\Data\ConciergeStatData;
use App\Models\Booking;
use App\Models\Concierge;
use Filament\Widgets\Widget;

class ConciergeStats extends Widget
{
    protected static string $view = 'livewire.concierge.concierge-stats';

    public ?Concierge $concierge;

    public string|int|array $columnSpan;

    public ConciergeStatData $stats;


    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Calculate for the current time frame
        $bookingsQuery = Booking::where('concierge_id', $this->concierge->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $conciergeEarnings = $bookingsQuery->sum('concierge_earnings');
        $charityEarnings = $bookingsQuery->sum('charity_earnings');
        $numberOfBookings = $bookingsQuery->count();

        // Calculate the concierge's contribution to the charity
        $charityPercentage = $this->concierge->user->charity_percentage / 100;
        $originalEarnings = $conciergeEarnings / (1 - $charityPercentage);
        $conciergeContribution = $originalEarnings - $conciergeEarnings;

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevBookingsQuery = Booking::where('concierge_id', $this->concierge->id)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $prevConciergeEarnings = $prevBookingsQuery->sum('concierge_earnings');
        $prevCharityEarnings = $prevBookingsQuery->sum('charity_earnings');
        $prevNumberOfBookings = $prevBookingsQuery->count();

        // Calculate the concierge's contribution to the charity for the previous time frame
        $prevOriginalEarnings = $prevConciergeEarnings / (1 - $charityPercentage);
        $prevConciergeContribution = $prevOriginalEarnings - $prevConciergeEarnings;

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame
        $this->stats = new ConciergeStatData([
            'current' => [
                'original_earnings' => $originalEarnings,
                'concierge_earnings' => $conciergeEarnings,
                'charity_earnings' => $charityEarnings,
                'number_of_bookings' => $numberOfBookings,
                'concierge_contribution' => $conciergeContribution,
            ],
            'previous' => [
                'original_earnings' => $prevOriginalEarnings,
                'concierge_earnings' => $prevConciergeEarnings,
                'charity_earnings' => $prevCharityEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
                'concierge_contribution' => $prevConciergeContribution,
            ],
            'difference' => [
                'original_earnings' => $originalEarnings - $prevOriginalEarnings,
                'original_earnings_up' => $originalEarnings >= $prevOriginalEarnings,
                'concierge_earnings' => $conciergeEarnings - $prevConciergeEarnings,
                'concierge_earnings_up' => $conciergeEarnings >= $prevConciergeEarnings,
                'charity_earnings' => $charityEarnings - $prevCharityEarnings,
                'charity_earnings_up' => $charityEarnings >= $prevCharityEarnings,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
                'concierge_contribution' => $conciergeContribution - $prevConciergeContribution,
                'concierge_contribution_up' => $conciergeContribution >= $prevConciergeContribution,
            ],
            'formatted' => [
                'original_earnings' => $this->formatNumber($originalEarnings),
                'concierge_earnings' => $this->formatNumber($conciergeEarnings),
                'charity_earnings' => $this->formatNumber($charityEarnings),
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'concierge_contribution' => $this->formatNumber($conciergeContribution),
                'difference' => [
                    'original_earnings' => $this->formatNumber($originalEarnings - $prevOriginalEarnings),
                    'concierge_earnings' => $this->formatNumber($conciergeEarnings - $prevConciergeEarnings),
                    'charity_earnings' => $this->formatNumber($charityEarnings - $prevCharityEarnings),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                    'concierge_contribution' => $this->formatNumber($conciergeContribution - $prevConciergeContribution),
                ],
            ],
        ]);

        ds($this->stats);
    }

    private function formatNumber($number): string
    {
        $number = round($number / 100); // Convert to dollars from cents and round to nearest whole number
        if ($number >= 1000) {
            return '$' . round($number / 1000) . 'k'; // Convert to k if number is greater than or equal to 1000
        }

        return '$' . $number; // Otherwise, return the whole number
    }
}
