<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingCreateSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingCreate')
            ->properties(
                Schema::string('date')
                    ->description('The date of the booking in YYYY-MM-DD format')
                    ->format('date')
                    ->example('2025-07-01'),
                Schema::integer('schedule_template_id')
                    ->description('The ID of the schedule template for the booking')
                    ->example(1),
                Schema::integer('guest_count')
                    ->description('The number of guests for the booking')
                    ->example(2)
            )
            ->required('date', 'schedule_template_id', 'guest_count');
    }
}
