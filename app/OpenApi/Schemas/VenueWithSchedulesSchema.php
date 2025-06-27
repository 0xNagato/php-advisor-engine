<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VenueWithSchedulesSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VenueWithSchedules')
            ->properties(
                Schema::integer('id')->description('The venue ID'),
                Schema::string('name')->description('The venue name'),
                Schema::string('status')->description('The venue status'),
                Schema::string('logo')->description('The venue logo URL')->nullable(),
                Schema::boolean('non_prime_time')->description('Whether the venue has non-prime time slots'),
                Schema::object('business_hours')->description('The venue business hours'),
                Schema::integer('tier')->description('The venue tier')->nullable(),
                Schema::string('tier_label')->description('The label for the venue tier'),
                Schema::array('schedules')
                    ->items(VenueScheduleSchema::ref())
                    ->description('The venue schedules')
            );
    }
}
