<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BookingCalculationData extends Data
{
    public function __construct(
        public int $totalFee,

        public int $restaurantPayoutPercentage,
        public int $restaurantCharityPercentage,
        public int $restaurantEarned,
        public int $restaurantCharityEarned,

        public int $conciergePayoutPercentage,
        public int $conciergeCharityPercentage,
        public int $conciergeEarned,
        public int $conciergeCharityEarned,

        public int $partnerRestaurantPayoutPercentage,
        public int $partnerRestaurantEarned,

        public int $partnerConciergePayoutPercentage,
        public int $partnerConciergeEarned,

        public int $platformPayoutPercentage,
        public int $platformEarned,
        public int $platformCharityEarned,

        public int $charityTotalEarned,
    ) {}

    public function calculateTotalEarnings(): int
    {
        return $this->restaurantEarned
            + $this->conciergeEarned
            + $this->partnerRestaurantEarned
            + $this->partnerConciergeEarned
            + $this->platformEarned;
    }
}
