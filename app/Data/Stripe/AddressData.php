<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class AddressData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public null $city,
        public null $line1,
        public null $line2,
        public null $state,
        public null $country,
        public null $postalCode
    )
    {
    }
}
