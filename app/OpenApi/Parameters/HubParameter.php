<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class HubParameter extends ParametersFactory
{
    /**
     * @return Parameter[]
     */
    public function build(): array
    {
        return [
            Parameter::query()
                ->name('date')
                ->description('The date for which to retrieve venue schedules (format: YYYY-MM-DD)')
                ->required(true)
                ->schema(Schema::string()->format('date')),

            Parameter::query()
                ->name('guest_count')
                ->description('Number of guests for the reservation')
                ->required(true)
                ->schema(Schema::integer()->minimum(1)),

            Parameter::query()
                ->name('reservation_time')
                ->description('Time of the reservation (format: HH:MM:SS)')
                ->required(true)
                ->schema(Schema::string()->format('time')),

            Parameter::query()
                ->name('timeslot_count')
                ->description('Number of timeslots to return (default: 5)')
                ->required(false)
                ->schema(Schema::integer()->minimum(1)->maximum(10)),

            Parameter::query()
                ->name('time_slot_offset')
                ->description('Offset for timeslots (default: 1)')
                ->required(false)
                ->schema(Schema::integer()->minimum(0)->maximum(10)),

            Parameter::query()
                ->name('venue_id')
                ->description('ID of the venue to get schedules for')
                ->required(true)
                ->schema(Schema::integer()),
        ];
    }
}
