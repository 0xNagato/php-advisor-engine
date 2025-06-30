<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VenueScheduleSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VenueSchedule')
            ->properties(
                Schema::integer('id')->description('The schedule ID'),
                Schema::integer('schedule_template_id')->description('The ID of the schedule template'),
                Schema::boolean('is_bookable')->description('Whether the schedule is bookable'),
                Schema::boolean('prime_time')->description('Whether the schedule is during prime time'),
                Schema::object('time')
                    ->properties(
                        Schema::string('value')->description('The formatted time (e.g., "7:00 PM")')->example('7:00 PM'),
                        Schema::string('raw')->description('The raw time in 24-hour format (HH:MM:SS)')->example('19:00:00')
                    ),
                Schema::string('date')->description('The booking date in YYYY-MM-DD format')->example('2025-07-01'),
                Schema::string('fee')->description('The fee for the booking')->example('$50'),
                Schema::boolean('has_low_inventory')->description('Whether the schedule has low inventory'),
                Schema::boolean('is_available')->description('Whether the venue was open/available for this specific time slot'),
                Schema::boolean('remaining_tables')->description('Number of tables still available for booking at this time slot'),
            );
    }
}
