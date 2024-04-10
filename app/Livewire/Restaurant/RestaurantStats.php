<?php

namespace App\Livewire\Restaurant;

use App\Data\RestaurantStatData;
use App\Models\Earning;
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

        // Get all earnings related to the restaurant
        $restaurantEarningsQuery = Earning::where('user_id', $this->restaurant->user_id)
            ->whereIn('type', ['restaurant'])
            ->whereBetween('confirmed_at', [$startDate, $endDate]);

        // Calculate restaurant earnings as the sum of amount
        $restaurantEarnings = $restaurantEarningsQuery->sum('amount');

        $numberOfBookings = $restaurantEarningsQuery->count();

        // Calculate for the previous time frame
        $timeFrameLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($timeFrameLength);
        $prevEndDate = $endDate->copy()->subDays($timeFrameLength);

        $prevRestaurantEarningsQuery = Earning::where('user_id', $this->restaurant->user_id)
            ->whereIn('type', ['restaurant'])
            ->whereBetween('confirmed_at', [$prevStartDate, $prevEndDate]);

        // Calculate previous restaurant earnings as the sum of amount
        $prevRestaurantEarnings = $prevRestaurantEarningsQuery->sum('amount');

        $prevNumberOfBookings = $prevRestaurantEarningsQuery->count();

        // Calculate the difference for each point and add a new property indicating if it was up or down from the previous time frame.
        $this->stats = new RestaurantStatData([
            'current' => [
                'original_earnings' => $restaurantEarnings,
                'restaurant_earnings' => $restaurantEarnings,
                'charity_earnings' => 0, // Assuming charity earnings are not applicable here
                'number_of_bookings' => $numberOfBookings,
                'restaurant_contribution' => $restaurantEarnings,
            ],
            'previous' => [
                'original_earnings' => $prevRestaurantEarnings,
                'restaurant_earnings' => $prevRestaurantEarnings,
                'charity_earnings' => 0, // Assuming charity earnings are not applicable here
                'number_of_bookings' => $prevNumberOfBookings,
                'restaurant_contribution' => $prevRestaurantEarnings,
            ],
            'difference' => [
                'original_earnings' => $restaurantEarnings - $prevRestaurantEarnings,
                'original_earnings_up' => $restaurantEarnings >= $prevRestaurantEarnings,
                'restaurant_earnings' => $restaurantEarnings - $prevRestaurantEarnings,
                'restaurant_earnings_up' => $restaurantEarnings >= $prevRestaurantEarnings,
                'charity_earnings' => 0, // Assuming charity earnings are not applicable here
                'charity_earnings_up' => true, // Assuming charity earnings are not applicable here
                'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings,
                'number_of_bookings_up' => $numberOfBookings >= $prevNumberOfBookings,
                'restaurant_contribution' => $restaurantEarnings - $prevRestaurantEarnings,
                'restaurant_contribution_up' => $restaurantEarnings >= $prevRestaurantEarnings,
            ],
            'formatted' => [
                'original_earnings' => $this->formatNumber($restaurantEarnings),
                'restaurant_earnings' => $this->formatNumber($restaurantEarnings),
                'charity_earnings' => $this->formatNumber(0), // Assuming charity earnings are not applicable here
                'number_of_bookings' => $numberOfBookings, // Assuming this is an integer count, no need to format
                'restaurant_contribution' => $this->formatNumber($restaurantEarnings),
                'difference' => [
                    'original_earnings' => $this->formatNumber($restaurantEarnings - $prevRestaurantEarnings),
                    'restaurant_earnings' => $this->formatNumber($restaurantEarnings - $prevRestaurantEarnings),
                    'charity_earnings' => $this->formatNumber(0), // Assuming charity earnings are not applicable here
                    'number_of_bookings' => $numberOfBookings - $prevNumberOfBookings, // Assuming this is an integer count, no need to format
                    'restaurant_contribution' => $this->formatNumber($restaurantEarnings - $prevRestaurantEarnings),
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

        return money($number, 'USD', true);
    }
}
