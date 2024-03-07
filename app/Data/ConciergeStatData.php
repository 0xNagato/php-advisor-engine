<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ConciergeStatData extends Data implements Wireable
{
    use WireableData;

    /**
     * @var array{
     *     original_earnings: float,
     *     concierge_earnings: float,
     *     charity_earnings: float,
     *     number_of_bookings: int,
     *     concierge_contribution: float
     * }
     */
    public array $current;

    /**
     * @var array{
     *     original_earnings: float,
     *     concierge_earnings: float,
     *     charity_earnings: float,
     *     number_of_bookings: int,
     *     concierge_contribution: float
     * }
     */
    public array $previous;

    /**
     * @var array{
     *     original_earnings: float,
     *     original_earnings_up: bool,
     *     concierge_earnings: float,
     *     concierge_earnings_up: bool,
     *     charity_earnings: float,
     *     charity_earnings_up: bool,
     *     number_of_bookings: int,
     *     number_of_bookings_up: bool,
     *     concierge_contribution: float,
     *     concierge_contribution_up: bool
     * }
     */
    public array $difference;

    /**
     * @var array{
     *     original_earnings: string,
     *     concierge_earnings: string,
     *     charity_earnings: string,
     *     number_of_bookings: int,
     *     concierge_contribution: string,
     *     difference: array{
     *         original_earnings: string,
     *         concierge_earnings: string,
     *         charity_earnings: string,
     *         number_of_bookings: int,
     *         concierge_contribution: string
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
