<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class TimeslotSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('Timeslot')
            ->properties(
                Schema::string('label')
                    ->description('The formatted time label (e.g., "7:00 PM")')
                    ->example('7:00 PM'),
                Schema::string('value')
                    ->description('The time value in 24-hour format (HH:MM:SS)')
                    ->example('19:00:00'),
                Schema::boolean('available')
                    ->description('Whether the timeslot is available for booking')
                    ->example(true)
            )
            ->required('label', 'value', 'available');
    }
}
