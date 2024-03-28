<?php

namespace App\Livewire\Concierge;

use App\Data\ConciergeStatData;
use App\Models\Booking;
use App\Models\Concierge;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class ConciergeStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.concierge.concierge-stats';

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

        // Calculate for the current time frame
        $bookingsQuery = Booking::where('concierge_id', $this->concierge->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $conciergeEarnings = $bookingsQuery->sum('concierge_earnings');
        $numberOfBookings = $bookingsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevBookingsQuery = Booking::where('concierge_id', $this->concierge->id)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $prevConciergeEarnings = $prevBookingsQuery->sum('concierge_earnings');
        $prevNumberOfBookings = $prevBookingsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new ConciergeStatData([
            'current' => [
                'concierge_earnings' => $conciergeEarnings,
                'number_of_bookings' => $numberOfBookings,
                'charity_earnings' => 0,
                'concierge_contribution' => 0,
            ],
            'previous' => [
                'concierge_earnings' => $prevConciergeEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
                'charity_earnings' => 0,
                'concierge_contribution' => 0,
            ],
            'difference' => [
                'concierge_earnings' => $conciergeEarnings - $prevConciergeEarnings,
                'concierge_earnings_up' => $conciergeEarnings >= $prevConciergeEarnings,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
                'charity_earnings' => 0,
                'charity_earnings_up' => false,
                'concierge_contribution' => 0,
                'concierge_contribution_up' => false,
            ],
            'formatted' => [
                'concierge_earnings' => $this->formatNumber($conciergeEarnings),
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'charity_earnings' => '$0',
                'concierge_contribution' => '$0',
                'difference' => [
                    'concierge_earnings' => $this->formatNumber($conciergeEarnings - $prevConciergeEarnings),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                    'charity_earnings' => '$0',
                    'concierge_contribution' => '$0',
                ],
            ],
        ]);
    }

    private function formatNumber($number): string
    {
        $number = round($number / 100, 2); // Convert to dollars from cents and round to nearest two decimal places.
        if ($number >= 1000) {
            return '$'.number_format($number / 1000, 1).'k'; // Convert to k if number is greater than or equal to 1000 and keep one decimal place.
        }

        return '$'.$number; // Otherwise, return the number
    }
}
