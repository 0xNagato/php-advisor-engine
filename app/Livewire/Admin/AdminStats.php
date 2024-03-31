<?php

namespace App\Livewire\Admin;

use App\Data\AdminStatData;
use App\Models\Booking;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AdminStats extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.admin.admin-stats';
    protected static bool $isLazy = false;

    public AdminStatData $stats;

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
        $bookingsQuery = Booking::whereBetween('created_at', [$startDate, $endDate]);

        $platformEarnings = $bookingsQuery->sum('platform_earnings');
        $charityEarnings = $bookingsQuery->sum('charity_earnings');
        $numberOfBookings = $bookingsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevBookingsQuery = Booking::whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $prevPlatformEarnings = $prevBookingsQuery->sum('platform_earnings');
        $prevCharityEarnings = $prevBookingsQuery->sum('charity_earnings');
        $prevNumberOfBookings = $prevBookingsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new AdminStatData([
            'current' => [
                'platform_earnings' => $platformEarnings,
                'charity_earnings' => $charityEarnings,
                'number_of_bookings' => $numberOfBookings,
            ],
            'previous' => [
                'platform_earnings' => $prevPlatformEarnings,
                'charity_earnings' => $prevCharityEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
            ],
            'difference' => [
                'platform_earnings' => $platformEarnings - $prevPlatformEarnings,
                'platform_earnings_up' => $platformEarnings >= $prevPlatformEarnings,
                'charity_earnings' => $charityEarnings - $prevCharityEarnings,
                'charity_earnings_up' => $charityEarnings >= $prevCharityEarnings,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
            ],
            'formatted' => [
                'platform_earnings' => $this->formatNumber($platformEarnings),
                'charity_earnings' => $this->formatNumber($charityEarnings),
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'difference' => [
                    'platform_earnings' => $this->formatNumber($platformEarnings - $prevPlatformEarnings),
                    'charity_earnings' => $this->formatNumber($charityEarnings - $prevCharityEarnings),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                ],
            ],
        ]);
    }

    private function formatNumber($number): string
    {
        $number = round($number / 100, 2); // Convert to dollars from cents and round to nearest two decimal places.
        if ($number >= 1000) {
            return '$' . number_format($number / 1000, 1) . 'k'; // Convert to k if number is greater than or equal to 1000 and keep one decimal place.
        }

        return '$' . $number; // Otherwise, return the number
    }
}
