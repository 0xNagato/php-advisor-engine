<?php

namespace App\Services;

use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PrimaShortUrls
{
    protected const URLS = [
        'more-info' => 'https://bit.ly/PRIMAVIP',
        'how-it-works' => 'https://bit.ly/PRIMA4',
    ];

    public static function get(string $key): string
    {
        return Cache::rememberForever("prima_short_url_{$key}", function () use ($key) {
            try {
                // First check if the URL already exists with this key
                $existingUrl = \AshAllenDesign\ShortURL\Models\ShortURL::query()->where('url_key', $key)->first();

                if ($existingUrl) {
                    return $existingUrl->default_short_url;
                }

                // If not, create a new one
                return ShortURL::destinationUrl(static::URLS[$key])
                    ->urlKey($key)
                    ->make()
                    ->default_short_url;
            } catch (ShortURLException $e) {
                // If there's an error (like duplicate key), log it and try to retrieve the existing URL
                Log::warning("Error creating short URL for key {$key}: ".$e->getMessage());

                $existingUrl = \AshAllenDesign\ShortURL\Models\ShortURL::query()->where('url_key', $key)->first();

                if ($existingUrl) {
                    return $existingUrl->default_short_url;
                }

                // If we still can't find it, return the original URL as fallback
                return static::URLS[$key];
            }
        });
    }

    /**
     * Get or create a partner-specific venue onboarding URL
     *
     * @param  string  $partnerId  The ID of the partner
     * @return string The short URL for venue onboarding with partner ID prefilled
     */
    public static function getPartnerOnboardingUrl(string $partnerId): string
    {
        $cacheKey = "partner_onboarding_url_{$partnerId}";

        // First check if we have a cached URL
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        return Cache::rememberForever($cacheKey, function () use ($partnerId) {
            try {
                // Generate an encrypted token for this partner ID
                $token = Crypt::encrypt($partnerId);

                // Create a destination URL with the token
                $destinationUrl = route('onboarding', ['token' => $token]);

                // First check if we already have a short URL for this partner by checking
                // for an URL with the destination URL that contains our token
                $existingUrl = \AshAllenDesign\ShortURL\Models\ShortURL::query()
                    ->where('destination_url', $destinationUrl)
                    ->first();

                if ($existingUrl) {
                    return $existingUrl->default_short_url;
                }

                // We didn't find an existing URL with this destination, so create a new one
                $shortUrl = ShortURL::destinationUrl($destinationUrl)
                    ->trackVisits()
                    ->trackIPAddress()
                    ->make();

                Log::info("Created new onboarding URL for partner {$partnerId}");

                return $shortUrl->default_short_url;
            } catch (ShortURLException $e) {
                // If there's an error, log it and return the original URL as fallback
                Log::warning("Error creating partner onboarding URL for partner {$partnerId}: ".$e->getMessage());

                // Even in fallback, use encrypted token instead of raw ID
                $token = Crypt::encrypt($partnerId);

                return route('onboarding', ['token' => $token]);
            }
        });
    }
}
