<?php

namespace App\Services;

use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURL as ShortURLModel;
use Illuminate\Support\Facades\Cache;
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
                $existingUrl = ShortURLModel::where('url_key', $key)->first();

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

                $existingUrl = ShortURLModel::where('url_key', $key)->first();

                if ($existingUrl) {
                    return $existingUrl->default_short_url;
                }

                // If we still can't find it, return the original URL as fallback
                return static::URLS[$key];
            }
        });
    }
}
