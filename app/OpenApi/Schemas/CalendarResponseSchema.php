<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class CalendarResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('CalendarResponse')
            ->properties(
                Schema::object('data')
                    ->properties(
                        Schema::array('venues')
                            ->items(VenueWithSchedulesSchema::ref())
                            ->description('List of venues with their schedules'),
                        Schema::array('timeslots')
                            ->items(Schema::string())
                            ->description('List of timeslot headers (e.g., "7:00 PM", "7:30 PM")')
                            ->example(['7:00 PM', '7:30 PM', '8:00 PM', '8:30 PM', '9:00 PM'])
                    )
            );
    }
}
