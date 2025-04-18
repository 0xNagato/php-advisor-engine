<?php

namespace App\Traits;

trait HandlesPartySizeMapping
{
    /**
     * Get the allowed guest counts.
     *
     * @return array<int>
     */
    public function getAllowedGuestCounts(): array
    {
        return range(2, 8);
    }

    /**
     * Get the minimum allowed guest count.
     */
    public function getMinGuestCount(): int
    {
        return 2;
    }

    /**
     * Get the maximum allowed guest count.
     */
    public function getMaxGuestCount(): int
    {
        return 8;
    }

    /**
     * Determine the target schedule template party size based on guest count mapping.
     *
     * @param  int  $guestCount  The number of guests requested.
     * @return int|null The target party size for the template query, or null if not accommodatable.
     */
    public function getTargetPartySize(int $guestCount): ?int
    {
        if (! in_array($guestCount, $this->getAllowedGuestCounts())) {
            return null; // Guest count outside allowed range
        }

        return match (true) {
            $guestCount <= 2 => 2,
            $guestCount <= 4 => 4,
            $guestCount <= 6 => 6,
            $guestCount <= 8 => 8,
            default => null // Should not be reached due to initial check, but good practice
        };
    }
}
