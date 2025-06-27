<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class HubResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('HubResponse')
            ->properties(
                Schema::array('data')
                    ->items(
                        Schema::object()
                            ->properties(
                                Schema::array('schedulesByDate')
                                    ->items(VenueScheduleSchema::ref())
                                    ->description('List of schedules for the specified date'),
                                Schema::array('schedulesThisWeek')
                                    ->items(VenueScheduleSchema::ref())
                                    ->description('List of schedules for the upcoming week')
                            )
                    )
            );
    }
}
