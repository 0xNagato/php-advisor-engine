<?php

namespace App\Services;

use App\Data\SalesTaxData;
use RuntimeException;

class SalesTaxService
{
    protected array $taxRates = [
        'new_york' => 0.08875,
        'los_angeles' => 0.095,
        'miami' => 0.07,
        'toronto' => 0.13,
        'ibiza' => 0.21,
        'mykonos' => 0.24,
        'paris' => 0.2,
        'london' => 0.2,
        'st_tropez' => 0.2,
        'las_vegas' => 0.08,
        'dubai' => 0.05,
    ];

    public function calculateTax(string $city, int $amountInCents): SalesTaxData
    {
        $taxRate = $this->taxRates[$city] ?? throw new RuntimeException("Tax rate for city $city not found.");

        return new SalesTaxData(
            amountInCents: (int) round($amountInCents * $taxRate),
            city: $city,
            tax: $taxRate,
            taxWhole: (int) ($taxRate * 100),
        );
    }
}
