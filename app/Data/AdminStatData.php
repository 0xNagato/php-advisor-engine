<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class AdminStatData extends Data implements Wireable
{
    use WireableData;

    /**
     * @var array{
     *     platform_earnings: float,
     *     charity_earnings: float,
     *     number_of_bookings: int
     * }
     */
    public array $current;

    /**
     * @var array{
     *     platform_earnings: float,
     *     charity_earnings: float,
     *     number_of_bookings: int
     * }
     */
    public array $previous;

    /**
     * @var array{
     *     platform_earnings: float,
     *     platform_earnings_up: bool,
     *     charity_earnings: float,
     *     charity_earnings_up: bool,
     *     number_of_bookings: int,
     *     number_of_bookings_up: bool
     * }
     */
    public array $difference;

    /**
     * @var array{
     *     platform_earnings: string,
     *     charity_earnings: string,
     *     number_of_bookings: int,
     *     difference: array{
     *         platform_earnings: string,
     *         charity_earnings: string,
     *         number_of_bookings: int
     *     }
     * }
     */
    public array $formatted;

    public function __construct(array $stats)
    {
        $this->current = $stats['current'];
        $this->previous = $stats['previous'];
        $this->difference = $stats['difference'];
        $this->formatted = $stats['formatted'];
    }

    public static function fromArray(array $stats): self
    {
        return new self([
            'current' => $stats['current'],
            'previous' => $stats['previous'],
            'difference' => $stats['difference'],
            'formatted' => $stats['formatted'],
        ]);
    }
}
