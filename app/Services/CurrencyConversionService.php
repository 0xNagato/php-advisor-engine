<?php

namespace App\Services;

class CurrencyConversionService
{
    /**
     * Exchange rates from currency to USD. Hardcoded until we setup API access.
     *
     * @var array<string, float>
     */
    protected array $exchangeRates = [
        'USD' => 1,
        'EUR' => 1.1,
        'GBP' => 1.3,
        'CAD' => 0.7,
    ];

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
