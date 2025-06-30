<?php

namespace App\Services;

use App\Data\SalesTaxData;
use App\Models\Region;
use RuntimeException;

class SalesTaxService
{
    public function calculateTax(string|Region $region, int $amountInCents): SalesTaxData
    {
        // Convert string to Region if needed
        if (is_string($region)) {
            $regionObj = Region::query()->find($region);
            throw_unless($regionObj, new RuntimeException("Region '$region' not found."));
            $regionId = $region;
        } else {
            $regionObj = $region;
            $regionId = $region->id;
        }

        if (! $regionObj->taxable) {
            return new SalesTaxData(
                amountInCents: 0,
                region: $regionId,
                tax: 0,
                taxWhole: 0,
            );
        }

        $taxRate = $regionObj->tax_rate ?? throw new RuntimeException("Tax rate for $regionId not found.");

        return new SalesTaxData(
            amountInCents: (int) round($amountInCents * $taxRate),
            region: $regionId,
            tax: $taxRate,
            taxWhole: (int) ($taxRate * 100),
        );
    }
}
