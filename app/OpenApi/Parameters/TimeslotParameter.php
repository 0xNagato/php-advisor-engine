<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class TimeslotParameter extends ParametersFactory
{
    /**
     * @return Parameter[]
     */
    public function build(): array
    {
        return [
            Parameter::query()
                ->name('date')
                ->description('The date for which to retrieve timeslots (format: YYYY-MM-DD)')
                ->required(true)
                ->schema(Schema::string()->format('date')),

            Parameter::query()
                ->name('region')
                ->description('The ID of the region to filter timeslots by')
                ->required(false)
                ->schema(Schema::string()),
        ];
    }
}
