<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class OvercaptureData extends Data implements Wireable
{
    use WireableData;

    public function __construct(public ?string $status, public ?int $maximumAmountCapturable)
    {
    }
}
