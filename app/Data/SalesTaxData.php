<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class SalesTaxData extends Data
{
    public function __construct(
        public int $amountInCents,
        public string $region,
        public float $tax,
        public int $taxWhole,
    ) {
    }
}
