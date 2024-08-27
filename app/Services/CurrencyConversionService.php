<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class CurrencyConversionService
{
    private const string API_URL = 'https://openexchangerates.org/api/latest.json';

    private const string CACHE_KEY = 'exchange_rates';

    private const int CACHE_DURATION = 3600; // 1 hour

    private Client $httpClient;

    private array $exchangeRates;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->exchangeRates = $this->getExchangeRates();
    }

    private function getExchangeRates(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            $response = $this->httpClient->get(self::API_URL, [
                'query' => [
                    'app_id' => config('services.openexchangerates.app_id'),
                    'base' => 'USD',
                ],
            ]);

            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $data['rates'] ?? [];
        });
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
