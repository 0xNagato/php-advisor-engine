<?php

namespace App\Livewire\Partner;

use App\Data\PartnerStatData;
use App\Models\Booking;
use App\Models\Partner;
use Filament\Widgets\Widget;

class PartnerStats extends Widget
{
    protected static string $view = 'livewire.partner.partner-stats';

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

        // Get all bookings related to the partner
        $partnerWithBookings = Partner::withAllBookings()->find($this->partner->id);

        // Calculate for the current time frame
        $conciergeBookingsQuery = Booking::whereIn('id', $partnerWithBookings->conciergeBookings->pluck('id'))
            ->whereBetween('created_at', [$startDate, $endDate]);

        $restaurantBookingsQuery = Booking::whereIn('id', $partnerWithBookings->restaurantBookings->pluck('id'))
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Calculate partner earnings as the sum of partner_concierge_fee and partner_restaurant_fee
        $partnerConciergeEarnings = $conciergeBookingsQuery->sum('partner_concierge_fee');
        $partnerRestaurantEarnings = $restaurantBookingsQuery->sum('partner_restaurant_fee');

        $numberOfConciergeBookings = $conciergeBookingsQuery->count();
        $numberOfRestaurantBookings = $restaurantBookingsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevConciergeBookingsQuery = Booking::whereIn('id', $partnerWithBookings->conciergeBookings->pluck('id'))
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $prevRestaurantBookingsQuery = Booking::whereIn('id', $partnerWithBookings->restaurantBookings->pluck('id'))
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        // Calculate previous partner earnings as the sum of partner_concierge_fee and partner_restaurant_fee
        $prevPartnerConciergeEarnings = $prevConciergeBookingsQuery->sum('partner_concierge_fee');
        $prevPartnerRestaurantEarnings = $prevRestaurantBookingsQuery->sum('partner_restaurant_fee');

        $prevNumberOfConciergeBookings = $prevConciergeBookingsQuery->count();
        $prevNumberOfRestaurantBookings = $prevRestaurantBookingsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new PartnerStatData([
            'current' => [
                'partner_earnings' => $partnerConciergeEarnings + $partnerRestaurantEarnings,
                'number_of_bookings' => $numberOfConciergeBookings + $numberOfRestaurantBookings,
            ],
            'previous' => [
                'partner_earnings' => $prevPartnerConciergeEarnings + $prevPartnerRestaurantEarnings,
                'number_of_bookings' => $prevNumberOfConciergeBookings + $prevNumberOfRestaurantBookings,
            ],
            'difference' => [
                'partner_earnings' => ($partnerConciergeEarnings + $partnerRestaurantEarnings) - ($prevPartnerConciergeEarnings + $prevPartnerRestaurantEarnings),
                'partner_earnings_up' => ($partnerConciergeEarnings + $partnerRestaurantEarnings) >= ($prevPartnerConciergeEarnings + $prevPartnerRestaurantEarnings),
                'number_of_bookings' => ($numberOfConciergeBookings + $numberOfRestaurantBookings) - ($prevNumberOfConciergeBookings + $prevNumberOfRestaurantBookings),
                'number_of_bookings_up' => ($numberOfConciergeBookings + $numberOfRestaurantBookings) >= ($prevNumberOfConciergeBookings + $prevNumberOfRestaurantBookings),
            ],
            'formatted' => [
                'partner_earnings' => $this->formatNumber($partnerConciergeEarnings + $partnerRestaurantEarnings),
                'number_of_bookings' => $numberOfConciergeBookings + $numberOfRestaurantBookings, // Assuming this is an integer count, no need to format
                'difference' => [
                    'partner_earnings' => $this->formatNumber(($partnerConciergeEarnings + $partnerRestaurantEarnings) - ($prevPartnerConciergeEarnings + $prevPartnerRestaurantEarnings)),
                    'number_of_bookings' => ($numberOfConciergeBookings + $numberOfRestaurantBookings) - ($prevNumberOfConciergeBookings + $prevNumberOfRestaurantBookings), // Assuming this is an integer count, no need to format
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
