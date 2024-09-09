<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class BillingDetailsData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public AddressData $address
    ) {}
}
