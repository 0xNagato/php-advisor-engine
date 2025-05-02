<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait HandlesRegionValidation
{
    /**
     * Get user's valid region from their IP or use default fallback.
     *
     * @param  string|null  $ip  The user's IP address. Defaults to null (uses detected IP).
     * @return string The validated region or the default region.
     */
    public function resolveRegion(?string $ip = null): string
    {
        $defaultRegion = config('app.default_region', 'miami');
        $request = request();

        if (! $ip) {
            // Try to get the IP from Cloudflare headers first
            $ip = $request->header('CF-Connecting-IP');
            
            if (!$ip) {
                // Fall back to standard IP detection if header isn't present
                $ip = $request->ip();
            }
        }

        // Skip caching in local development environment
        if (app()->environment('local')) {
            return $this->resolveRegionFromIp($ip, $defaultRegion);
        }

        $cacheKey = "region_resolution_{$ip}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($ip, $defaultRegion) {
            return $this->resolveRegionFromIp($ip, $defaultRegion);
        });
    }

    /**
     * Core logic for resolving region from IP address
     * Extracted to avoid duplication between cached and non-cached paths
     */
    private function resolveRegionFromIp(string $ip, string $defaultRegion): string
    {
        try {
            $regionCode = null;
            $countryCode = null;
            
            // Attempt to fetch region and country code from IPAPI
            $ipApiData = $this->getRegionAndCountryCodeFromIpApi($ip);
            if ($ipApiData) {
                $regionCode = $ipApiData['region_code'];
                $countryCode = $ipApiData['country_code'];
            }

            // If IPAPI fails, try GeoJS as a fallback
            if (! $regionCode || ! $countryCode) {
                $geoJsData = $this->getRegionAndCountryCodeFromGeoJs($ip);
                if ($geoJsData) {
                    $regionCode = $geoJsData['region_code'];
                    $countryCode = $geoJsData['country_code'];
                }
            }

            // Map region code to Prima region using config values
            $regionMapping = config('app.region_code_mapping', []);
            if ($regionCode && isset($regionMapping[$regionCode])) {
                return $regionMapping[$regionCode];
            }
            
            // If region code not found, try mapping by country code
            return $this->getRegionByCountryCode($countryCode, $defaultRegion);
        } catch (Exception $e) {
            // Log errors for debugging purposes but don't crash
            logger()->error("Region resolution failed for IP {$ip}: ".$e->getMessage());
        }

        return $defaultRegion; // Fallback to the default region
    }

    /**
     * Fetch region and country code via IPAPI.
     *
     * @param  string  $ip  The user's IP address.
     * @return array|null Array containing 'region_code' and 'country_code', or null if failed.
     */
    private function getRegionAndCountryCodeFromIpApi(string $ip): ?array
    {
        try {
            $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json");

            if ($response->status() === 429) {
                // Hit the rate limit; return null to trigger fallback
                return null;
            }

            if ($response->successful()) {
                return [
                    'region_code' => $response->json('region_code') ?? null,
                    'country_code' => $response->json('country_code') ?? null,
                ];
            }
        } catch (Exception $e) {
            logger()->error('IPAPI Region and Country Code resolution error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Fetch region and country code via GeoJS.
     *
     * @param  string  $ip  The user's IP address.
     * @return array|null Array containing 'region_code' and 'country_code', or null if failed.
     */
    private function getRegionAndCountryCodeFromGeoJs(string $ip): ?array
    {
        try {
            $response = Http::timeout(5)->get("https://get.geojs.io/v1/ip/geo/{$ip}.json");

            if ($response->successful()) {
                return [
                    'region_code' => $response->json('region'),
                    'country_code' => $response->json('country_code') ?? null,
                ];
            }
        } catch (Exception $e) {
            logger()->error('GeoJS Region and Country Code resolution error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Determine region based on country code if no region matches.
     *
     * @param  string|null  $countryCode  The user's country code.
     * @param  string  $defaultRegion  The default region to return if country is not matched.
     * @return string The region based on country or the default region.
     */
    private function getRegionByCountryCode(?string $countryCode, string $defaultRegion): string
    {
        if (!$countryCode) {
            return $defaultRegion;
        }
        
        $countryMapping = config('app.country_region_mapping', []);
        return $countryMapping[$countryCode] ?? $defaultRegion;
    }
}