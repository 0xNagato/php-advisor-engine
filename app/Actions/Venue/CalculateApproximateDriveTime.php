<?php

namespace App\Actions\Venue;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class CalculateApproximateDriveTime
{
    use AsAction;

    public function handle(
        Collection $venues,
        ?float $userLat,
        ?float $userLng
    ): Collection {
        // If no user coordinates provided, return venues without drive time
        if (! $userLat || ! $userLng) {
            return $venues;
        }

        return $venues->map(function ($venue) use ($userLat, $userLng) {
            // Skip if venue doesn't have coordinates
            if (! $venue->latitude || ! $venue->longitude) {
                return $venue;
            }

            // Calculate distance using Haversine formula
            $distance = $this->calculateHaversineDistance(
                $userLat,
                $userLng,
                $venue->latitude,
                $venue->longitude
            );

            // Convert to approximate drive time
            // Using 1.3x multiplier for real roads vs straight line
            // 30 mph average speed in urban areas
            $approxMinutes = round(($distance * 1.3) / 30 * 60);

            // Set temporary attributes on the venue model
            $venue->setAttribute('approx_minutes', $approxMinutes);
            $venue->setAttribute('distance_miles', round($distance, 1));
            $venue->setAttribute('distance_km', round($distance * 1.60934, 1));

            return $venue;
        });
    }

    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param  float  $lat1  User latitude
     * @param  float  $lng1  User longitude
     * @param  float  $lat2  Venue latitude
     * @param  float  $lng2  Venue longitude
     * @return float Distance in miles
     */
    private function calculateHaversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 3959; // Earth's radius in miles

        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        // Calculate differences
        $latDiff = $lat2Rad - $lat1Rad;
        $lngDiff = $lng2Rad - $lng1Rad;

        // Haversine formula
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
