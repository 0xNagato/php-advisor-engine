<?php

namespace App\Livewire\Partner;

use App\Data\PartnerStatData;
use App\Models\Earning;
use App\Models\Partner;
use Filament\Widgets\Widget;

class PartnerStats extends Widget
{
    protected static string $view = 'livewire.partner.partner-stats';

    protected static bool $isLazy = false;

    public ?Partner $partner;

    public PartnerStatData $stats;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Get all earnings related to the partner
        $partnerEarningsQuery = Earning::where('user_id', $this->partner->user_id)
            ->whereIn('type', ['partner_concierge', 'partner_restaurant'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        // Calculate partner earnings as the sum of amount
        $partnerEarnings = $partnerEarningsQuery->sum('amount');

        $numberOfBookings = $partnerEarningsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevPartnerEarningsQuery = Earning::where('user_id', $this->partner->user_id)
            ->whereIn('type', ['partner_concierge', 'partner_restaurant'])
            ->whereBetween('confirmed_at', [$prevStartDate, $prevEndDate]);

        // Calculate previous partner earnings as the sum of amount
        $prevPartnerEarnings = $prevPartnerEarningsQuery->sum('amount');

        $prevNumberOfBookings = $prevPartnerEarningsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new PartnerStatData([
            'current' => [
                'partner_earnings' => $partnerEarnings,
                'number_of_bookings' => $numberOfBookings,
            ],
            'previous' => [
                'partner_earnings' => $prevPartnerEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
            ],
            'difference' => [
                'partner_earnings' => $partnerEarnings - $prevPartnerEarnings,
                'partner_earnings_up' => $partnerEarnings >= $prevPartnerEarnings,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
            ],
            'formatted' => [
                'partner_earnings' => $this->formatNumber($partnerEarnings),
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'difference' => [
                    'partner_earnings' => $this->formatNumber($partnerEarnings - $prevPartnerEarnings),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
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

        return money($number, 'USD', true);
    }
}
