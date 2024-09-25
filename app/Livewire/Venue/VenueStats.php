<?php

namespace App\Livewire\Venue;

use App\Data\VenueStatData;
use App\Models\Earning;
use App\Models\Region;
use App\Models\Venue;
use Filament\Widgets\Widget;

class VenueStats extends Widget
{
    protected static string $view = 'livewire.venue.venue-stats';

    protected static ?string $pollingInterval = null;

    public ?Venue $venue = null;

    public VenueStatData $stats;

    public string $currency;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $this->currency = Region::query()->find($this->venue->region)->currency;

        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Get all earnings related to the venue
        $venueEarningsQuery = Earning::query()->where('user_id', $this->venue->user_id)
            ->whereIn('type', ['venue'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        $venueBountyQuery = Earning::query()->where('user_id', $this->venue->user_id)
            ->whereIn('type', ['venue_paid'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        // Calculate venue earnings as the sum of amount
        $venueEarnings = $venueEarningsQuery->sum('amount');
        $venueBounty = abs($venueBountyQuery->sum('amount'));

        $numberOfBookings = $venueEarningsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevVenueEarningsQuery = Earning::query()->where('user_id', $this->venue->user_id)
            ->whereIn('type', ['venue'])
            ->whereBetween('confirmed_at', [$prevStartDate, $prevEndDate]);

        $prevVenueBountyQuery = Earning::query()->where('user_id', $this->venue->user_id)
            ->whereIn('type', ['venue_paid'])
            ->whereBetween('confirmed_at', [$prevStartDate, $prevEndDate]);

        // Calculate previous venue earnings as the sum of amount
        $prevVenueEarnings = $prevVenueEarningsQuery->sum('amount');
        $prevVenueBounty = abs($prevVenueBountyQuery->sum('amount'));

        $prevNumberOfBookings = $prevVenueEarningsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new VenueStatData([
            'current' => [
                'original_earnings' => $venueEarnings,
                'venue_earnings' => $venueEarnings,
                'number_of_bookings' => $numberOfBookings,
                'venue_contribution' => $venueEarnings,
            ],
            'previous' => [
                'original_earnings' => $prevVenueEarnings,
                'venue_earnings' => $prevVenueEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
                'venue_contribution' => $prevVenueEarnings,
            ],
            'difference' => [
                'original_earnings' => $venueEarnings - $prevVenueEarnings,
                'original_earnings_up' => $venueEarnings >= $prevVenueEarnings,
                'venue_earnings' => $venueEarnings - $prevVenueEarnings,
                'venue_earnings_up' => $venueEarnings >= $prevVenueEarnings,
                'venue_bounty' => $venueBounty - $prevVenueBounty,
                'venue_bounty_up' => $venueBounty >= $prevVenueBounty,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
                'venue_contribution' => $venueEarnings - $prevVenueEarnings,
                'venue_contribution_up' => $venueEarnings >= $prevVenueEarnings,
            ],
            'formatted' => [
                'original_earnings' => $this->formatNumber($venueEarnings),
                'venue_earnings' => $this->formatNumber($venueEarnings),
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'venue_contribution' => $this->formatNumber($venueEarnings),
                'venue_bounty' => $this->formatNumber($venueBounty),
                'difference' => [
                    'original_earnings' => $this->formatNumber($venueEarnings - $prevVenueEarnings),
                    'venue_earnings' => $this->formatNumber($venueEarnings - $prevVenueEarnings),
                    'venue_bounty' => $this->formatNumber($venueBounty - $prevVenueBounty),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                    'venue_contribution' => $this->formatNumber($venueEarnings - $prevVenueEarnings),
                ],
            ],
        ]);
    }

    private function formatNumber($number): string
    {
        return money($number, $this->currency);
    }
}
