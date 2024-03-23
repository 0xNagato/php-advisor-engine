<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ChecksData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $cvcCheck,
        public ?string $addressLine1Check,
        public ?string $addressPostalCodeCheck
    ) {
    }
}
