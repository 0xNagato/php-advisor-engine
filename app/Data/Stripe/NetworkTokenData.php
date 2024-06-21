<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class NetworkTokenData extends Data implements Wireable
{
    use WireableData;

    public function __construct(public bool $used) {}
}
