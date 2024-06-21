<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class IncrementalAuthorizationData extends Data implements Wireable
{
    use WireableData;

    public function __construct(public string $status) {}
}
