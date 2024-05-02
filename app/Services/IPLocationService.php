<?php

namespace App\Services;

use App\Data\IPData;
use App\Models\Region;

class IPLocationService
{
    public function getLocationData(string $ipAddress): IPData
    {
        $url = "http://ip-api.com/json/$ipAddress";
        $data = file_get_contents($url);

        return IPData::from(json_decode($data, true));
    }

    public function getClosestRegion(float $userLat, float $userLon)
    {
        $closestRegion = null;
        $minDistance = PHP_INT_MAX;

        foreach (Region::all() as $region) {
            $regionLat = $region['lat'];
            $regionLon = $region['lon'];

            $distance = $this->haversineDistance($userLat, $userLon, $regionLat, $regionLon);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestRegion = $region;
            }
        }

        return $closestRegion;
    }

    protected function haversineDistance($lat1, $long1, $lat2, $long2): float|int
    {
        $earthRadius = 6371; // in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLong = deg2rad($long2 - $long1);

        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2) + sin($dLong / 2) * sin($dLong / 2) * cos($lat1) * cos($lat2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function deg2rad($deg): float|int
    {
        return $deg * M_PI / 180;
    }
}
