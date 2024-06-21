<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class PaymentMethodDetailsData extends Data implements Wireable
{
    use WireableData;

    public function __construct(public CardData $card, public string $type) {}
}
