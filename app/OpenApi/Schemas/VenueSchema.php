<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VenueSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('Venue')
            ->properties(
                Schema::integer('id')
                    ->description('The unique identifier of the venue')
                    ->example(1),
                Schema::string('name')
                    ->description('The name of the venue')
                    ->example('The Grand Restaurant')
            )
            ->required('id', 'name');
    }
}
