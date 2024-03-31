<?php

namespace App\Livewire\Restaurant;

use App\Data\RestaurantStatData;
use App\Models\Booking;
use App\Models\Restaurant;
use Filament\Widgets\Widget;

class RestaurantStats extends Widget
{
    protected static string $view = 'livewire.restaurant.restaurant-stats';
    protected static bool $isLazy = false;

    public ?Restaurant $restaurant;

    public RestaurantStatData $stats;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Get all schedule IDs related to the restaurant
        $scheduleIds = $this->restaurant->schedules->pluck('id');

        // Calculate for the current time frame
        $bookingsQuery = Booking::whereIn('schedule_id', $scheduleIds)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $restaurantEarnings = $bookingsQuery->sum('restaurant_earnings');
        $charityEarnings = $bookingsQuery->sum('charity_earnings');
        $numberOfBookings = $bookingsQuery->count();

        // Calculate the restaurant's contribution to the charity
        $charityPercentage = $this->restaurant->user->charity_percentage / 100;
        $originalEarnings = $restaurantEarnings / (1 - $charityPercentage);
        $restaurantContribution = $originalEarnings - $restaurantEarnings;

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevBookingsQuery = Booking::whereIn('schedule_id', $scheduleIds)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $prevRestaurantEarnings = $prevBookingsQuery->sum('restaurant_earnings');
        $prevCharityEarnings = $prevBookingsQuery->sum('charity_earnings');
        $prevNumberOfBookings = $prevBookingsQuery->count();

        // Calculate the restaurant's contribution to the charity for the previous time frame.
        $prevOriginalEarnings = $prevRestaurantEarnings / (1 - $charityPercentage);
        $prevRestaurantContribution = $prevOriginalEarnings - $prevRestaurantEarnings;

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new RestaurantStatData([
            'current' => [
                'original_earnings' => $originalEarnings,
                'restaurant_earnings' => $restaurantEarnings,
                'charity_earnings' => $charityEarnings,
                'number_of_bookings' => $numberOfBookings,
                'restaurant_contribution' => $restaurantContribution,
            ],
            'previous' => [
                'original_earnings' => $prevOriginalEarnings,
                'restaurant_earnings' => $prevRestaurantEarnings,
                'charity_earnings' => $prevCharityEarnings,
                'number_of_bookings' => $prevNumberOfBookings,
                'restaurant_contribution' => $prevRestaurantContribution,
            ],
            'difference' => [
                'original_earnings' => $originalEarnings - $prevOriginalEarnings,
                'original_earnings_up' => $originalEarnings >= $prevOriginalEarnings,
                'restaurant_earnings' => $restaurantEarnings - $prevRestaurantEarnings,
                'restaurant_earnings_up' => $restaurantEarnings >= $prevRestaurantEarnings,
                'charity_earnings' => $charityEarnings - $prevCharityEarnings,
                'charity_earnings_up' => $charityEarnings >= $prevCharityEarnings,
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
                'restaurant_contribution' => $restaurantContribution - $prevRestaurantContribution,
                'restaurant_contribution_up' => $restaurantContribution >= $prevRestaurantContribution,
            ],
            'formatted' => [
                'original_earnings' => $this->formatNumber($originalEarnings),
                'restaurant_earnings' => $this->formatNumber($restaurantEarnings),
                'charity_earnings' => $this->formatNumber($charityEarnings),
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'restaurant_contribution' => $this->formatNumber($restaurantContribution),
                'difference' => [
                    'original_earnings' => $this->formatNumber($originalEarnings - $prevOriginalEarnings),
                    'restaurant_earnings' => $this->formatNumber($restaurantEarnings - $prevRestaurantEarnings),
                    'charity_earnings' => $this->formatNumber($charityEarnings - $prevCharityEarnings),
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                    'restaurant_contribution' => $this->formatNumber($restaurantContribution - $prevRestaurantContribution),
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
