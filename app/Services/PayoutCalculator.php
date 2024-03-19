<?php

namespace App\Services;

use App\Data\BookingCalculationData;
use App\Models\Booking;

class PayoutCalculator
{
    protected int $platformPayoutPercentage;

    protected int $platformCharityPercentage = 5;

    protected int $restaurantPayoutPercentage;

    protected int $restaurantCharityPercentage;

    protected int $conciergePayoutPercentage;

    protected int $conciergeCharityPercentage;

    protected int $partnerConciergePayoutPercentage;

    protected int $partnerRestaurantPayoutPercentage;

    public function __construct(private readonly Booking $booking)
    {
        $this->restaurantPayoutPercentage = $this->booking->schedule->restaurant->payout_restaurant;
        $this->restaurantCharityPercentage = $this->booking->schedule->restaurant->user->charity_percentage;
        $this->conciergePayoutPercentage = $this->booking->concierge->payout_percentage;
        $this->conciergeCharityPercentage = $this->booking->concierge->user->charity_percentage;
        $this->partnerRestaurantPayoutPercentage = $this->booking->partnerRestaurant->percentage;
        $this->partnerConciergePayoutPercentage = $this->booking->partnerConcierge->percentage;
        $this->platformPayoutPercentage = 100 - $this->restaurantPayoutPercentage - $this->conciergePayoutPercentage - $this->partnerRestaurantPayoutPercentage;
    }

    public function calculate(): BookingCalculationData
    {
        return new BookingCalculationData(
            totalFee: $this->booking->total_fee,
            restaurantPayoutPercentage: $this->restaurantPayoutPercentage,
            restaurantCharityPercentage: $this->restaurantCharityPercentage,
            restaurantEarned: $this->calculateRestaurant()['earned'],
            restaurantCharityEarned: $this->calculateRestaurant()['charity'],
            conciergePayoutPercentage: $this->conciergePayoutPercentage,
            conciergeCharityPercentage: $this->conciergeCharityPercentage,
            conciergeEarned: $this->calculateConcierge()['earned'],
            conciergeCharityEarned: $this->calculateConcierge()['charity'],
            partnerRestaurantPayoutPercentage: $this->partnerRestaurantPayoutPercentage,
            partnerRestaurantEarned: $this->calculatePartnerRestaurant(),
            partnerConciergePayoutPercentage: $this->partnerConciergePayoutPercentage,
            partnerConciergeEarned: $this->calculatePartnerConcierge(),
            platformPayoutPercentage: $this->platformPayoutPercentage,
            platformEarned: $this->calculatePlatform()['earned'],
            platformCharityEarned: $this->calculatePlatform()['charity'],
            charityTotalEarned: $this->calculateRestaurant()['charity'] + $this->calculateConcierge()['charity'] + $this->calculatePlatform()['charity']
        );
    }

    /**
     * Calculate the total, charity amount and earned amount for the restaurant.
     *
     * @return array{
     *     total: int,
     *     charity: int,
     *     earned: int
     * } The total, charity and earned amount
     */
    private function calculateRestaurant(): array
    {
        $totalAmount = $this->booking->total_fee * ($this->restaurantPayoutPercentage / 100);
        $charityAmount = $totalAmount * ($this->restaurantCharityPercentage / 100);

        return ['total' => $totalAmount, 'charity' => $charityAmount, 'earned' => $totalAmount - $charityAmount];
    }

    /**
     * Calculate the total, charity amount and earned amount for the concierge.
     *
     * @return array{
     *     total: int,
     *     charity: int,
     *     earned: int
     * } The total, charity and earned amount
     */
    private function calculateConcierge(): array
    {
        $totalAmount = $this->booking->total_fee * ($this->conciergePayoutPercentage / 100);
        $charityAmount = $totalAmount * ($this->conciergeCharityPercentage / 100);

        return ['total' => $totalAmount, 'charity' => $charityAmount, 'earned' => $totalAmount - $charityAmount];
    }

    private function calculatePartnerRestaurant(): int
    {
        return $this->booking->total_fee * ($this->partnerRestaurantPayoutPercentage / 100);
    }

    private function calculatePartnerConcierge(): int
    {
        return $this->calculatePlatformTotalBeforeCharity() * ($this->partnerConciergePayoutPercentage / 100);
    }

    /**
     * Calculate the total amount for the platform before charity.
     *
     * @return int The total amount
     */
    private function calculatePlatformTotalBeforeCharity(): int
    {
        return $this->booking->total_fee * ($this->platformPayoutPercentage / 100);
    }

    /**
     * Calculate the total, charity amount and earned amount for the platform.
     *
     * @return array{
     *     total: int,
     *     charity: int,
     *     earned: int
     * } The total, charity and earned amount
     */
    private function calculatePlatform(): array
    {
        $totalAmount = $this->calculatePlatformTotalBeforeCharity();
        $charityAmount = $totalAmount * ($this->platformCharityPercentage / 100);
        $partnerConciergePayout = $this->calculatePartnerConcierge();

        return ['total' => $totalAmount, 'charity' => $charityAmount, 'earned' => $totalAmount - $charityAmount - $partnerConciergePayout];
    }
}
