<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class BillingDetailsData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public null $name,
        public null $email,
        public null $phone,
        public AddressData $address
    ) {
    }
}
