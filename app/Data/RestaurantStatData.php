<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class RestaurantStatData extends Data implements Wireable
{
    use WireableData;

    /**
     * @var array{
     *     original_earnings: float,
     *     restaurant_earnings: float,
     *     charity_earnings: float,
     *     number_of_bookings: int,
     *     restaurant_contribution: float
     * }
     */
    public array $current;

    /**
     * @var array{
     *     original_earnings: float,
     *     restaurant_earnings: float,
     *     charity_earnings: float,
     *     number_of_bookings: int,
     *     restaurant_contribution: float
     * }
     */
    public array $previous;

    /**
     * @var array{
     *     original_earnings: float,
     *     original_earnings_up: bool,
     *     restaurant_earnings: float,
     *     restaurant_earnings_up: bool,
     *     charity_earnings: float,
     *     charity_earnings_up: bool,
     *     number_of_bookings: int,
     *     number_of_bookings_up: bool,
     *     restaurant_contribution: float,
     *     restaurant_contribution_up: bool
     * }
     */
    public array $difference;

    /**
     * @var array{
     *     original_earnings: string,
     *     restaurant_earnings: string,
     *     charity_earnings: string,
     *     number_of_bookings: int,
     *     restaurant_contribution: string,
     *     difference: array{
     *         original_earnings: string,
     *         restaurant_earnings: string,
     *         charity_earnings: string,
     *         number_of_bookings: int,
     *         restaurant_contribution: string
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
}
