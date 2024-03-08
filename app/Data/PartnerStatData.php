<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;

class PartnerStatData implements Wireable
{
    use WireableData;

    /**
     * @var array{
     *     partner_earnings: float,
     *     number_of_bookings: int
     * }
     */
    public array $current;

    /**
     * @var array{
     *     partner_earnings: float,
     *     number_of_bookings: int
     * }
     */
    public array $previous;

    /**
     * @var array{
     *     partner_earnings: float,
     *     partner_earnings_up: bool,
     *     number_of_bookings: int,
     *     number_of_bookings_up: bool
     * }
     */
    public array $difference;

    /**
     * @var array{
     *     partner_earnings: string,
     *     number_of_bookings: int,
     *     difference: array{
     *         partner_earnings: string,
     *         number_of_bookings: int
     *     }
     * }
     */
    public array $formatted;

    /**
     * @param array{
     *     current: array{
     *         partner_earnings: float,
     *         number_of_bookings: int
     *     },
     *     previous: array{
     *         partner_earnings: float,
     *         number_of_bookings: int
     *     },
     *     difference: array{
     *         partner_earnings: float,
     *         partner_earnings_up: bool,
     *         number_of_bookings: int,
     *         number_of_bookings_up: bool
     *     },
     *     formatted: array{
     *         partner_earnings: string,
     *         number_of_bookings: int,
     *         difference: array{
     *             partner_earnings: string,
     *             number_of_bookings: int
     *         }
     *     }
     * } $data
     */
    public function __construct(array $data)
    {
        $this->current = $data['current'];
        $this->previous = $data['previous'];
        $this->difference = $data['difference'];
        $this->formatted = $data['formatted'];
    }

    public function toArray(): array
    {
        return [
            'current' => $this->current,
            'previous' => $this->previous,
            'difference' => $this->difference,
            'formatted' => $this->formatted,
        ];
    }
}
