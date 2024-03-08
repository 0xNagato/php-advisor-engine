<?php

namespace App\Services;

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
        $this->platformPayoutPercentage = 100 - $this->restaurantPayoutPercentage;

        $this->partnerConciergePayoutPercentage = $this->booking->partnerConcierge->partner();
        $this->partnerRestaurantPayoutPercentage = $this->booking->partnerRestaurant->payout_percentage;
    }

    public function calculate()
    {

    }
}
