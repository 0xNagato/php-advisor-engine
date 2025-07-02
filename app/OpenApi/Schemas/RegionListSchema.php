<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class RegionListSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('RegionList')
            ->properties(
                Schema::object('data')
                    ->additionalProperties(Schema::string())
                    ->description('A mapping of region IDs to region names')
                    ->example(['new_york' => 'New York', 'los_angeles' => 'Los Angeles', 'miami' => 'Miami'])
            );
    }
}
