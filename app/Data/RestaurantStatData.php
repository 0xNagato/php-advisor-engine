<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class RestaurantStatData extends Data implements Wireable
{
    use WireableData;

    public array $current;

    public array $previous;

    public array $difference;

    public array $formatted;

    public function __construct(array $stats)
    {
        $this->current = $stats['current'];
        $this->previous = $stats['previous'];
        $this->difference = $stats['difference'];
        $this->formatted = $stats['formatted'];
    }
}
