<?php

namespace App\Data\SpecialRequest;

use Carbon\CarbonInterface;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class SpecialRequestConversionData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public string $name,
        public int $minimum_spend,
        public int $commission_requested_percentage,
        public ?string $message,
        public CarbonInterface $create_at,
    ) {
    }
}
