<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class OutcomeData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $type,
        public ?string $reason,
        public ?string $riskLevel,
        public ?int    $riskScore,
        public ?string $networkStatus,
        public ?string $sellerMessage
    )
    {
    }
}
