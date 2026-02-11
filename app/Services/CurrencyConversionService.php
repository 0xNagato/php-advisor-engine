<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyConversionService
{
    private const string API_URL = 'https://openexchangerates.org/api/latest.json';

    private const string CACHE_KEY = 'exchange_rates';

    private const int CACHE_DURATION = 3600 * 12;

    private array $exchangeRates;

    public function __construct(private readonly Client $httpClient)
    {
        $this->exchangeRates = $this->getExchangeRates();
    }

    private function getExchangeRates(): array
    {
        $appId = (string) (config('services.openexchangerates.app_id') ?? '');

        if ($appId === '') {
            Log::notice('OpenExchangeRates app_id is missing; skipping live rate fetch and using default 1:1 rates.');

            return [];
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = $this->httpClient->get(self::API_URL, [
                'query' => [
                    'app_id' => $appId,
                    'base' => 'USD',
                ],
                'timeout' => 5,
                'connect_timeout' => 3,
            ]);

            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $rates = $data['rates'] ?? [];

            Cache::put(self::CACHE_KEY, $rates, self::CACHE_DURATION);

            return $rates;
        } catch (\Throwable $e) {
            Log::warning('OpenExchangeRates fetch failed: '.$e->getMessage());

            return [];
        }
    }

    public function convertToUSD(array $amounts): float
    {
        $total = 0;

        foreach ($amounts as $currency => $amount) {
            $rate = $this->exchangeRates[$currency] ?? 1;
            $total += ($amount / 100) / $rate;
        }

        return $total;
    }

    public function convertFromUSD(float $amount, string $toCurrency): float
    {
        $rate = $this->exchangeRates[$toCurrency] ?? 1;

        return $amount * $rate;
    }
}
