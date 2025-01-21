<?php

namespace App\Services;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\Cache;

class PrimaShortUrls
{
    protected const URLS = [
        'more-info' => 'https://bit.ly/PRIMAVIP',
        'how-it-works' => 'https://bit.ly/PRIMA4',
    ];

    public static function get(string $key): string
    {
        return Cache::rememberForever("prima_short_url_{$key}", fn () => ShortURL::destinationUrl(static::URLS[$key])
            ->urlKey($key)
            ->make()
            ->default_short_url);
    }
}
