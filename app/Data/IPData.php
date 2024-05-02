<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IPData extends Data
{
    public function __construct(
        public string $country,
        public string $countryCode,
        public string $region,
        public string $regionName,
        public string $city,
        public string $zip,
        public float $lat,
        public float $lon,
        public string $timezone,
        public string $isp,
        public string $org,
        public string $as,
        public string $query
    ) {
    }
}
