<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class AddressData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $city,
        public ?string $line1,
        public ?string $line2,
        public ?string $state,
        public ?string $country,
        public ?string $postalCode
    ) {}
}
