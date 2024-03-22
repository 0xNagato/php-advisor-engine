<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class SalesTaxData extends Data
{
    public function __construct(
        public int    $amountInCents,
        public string $city,
        public float  $tax,
        public int    $taxWhole,
    )
    {
    }
}
