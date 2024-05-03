<?php

namespace App\Services;

use App\Data\SalesTaxData;
use App\Models\Region;
use RuntimeException;

class SalesTaxService
{
    public function calculateTax(string $region, int $amountInCents, bool $noTax = false): SalesTaxData
    {
        if ($noTax) {
            return new SalesTaxData(
                amountInCents: 0,
                region: $region,
                tax: 0,
                taxWhole: 0,
            );
        }

        $taxRate = Region::find($region)->tax_rate ?? throw new RuntimeException("Tax rate for $region not found.");

        return new SalesTaxData(
            amountInCents: (int) round($amountInCents * $taxRate),
            region: $region,
            tax: $taxRate,
            taxWhole: (int) ($taxRate * 100),
        );
    }
}
