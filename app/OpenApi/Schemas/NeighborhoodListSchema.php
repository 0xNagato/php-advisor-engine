<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class NeighborhoodListSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('NeighborhoodList')
            ->properties(
                Schema::object('data')
                    ->additionalProperties(Schema::string())
                    ->description('A mapping of neighborhood IDs to neighborhood names')
                    ->example(['downtown' => 'Downtown', 'uptown' => 'Uptown', 'midtown' => 'Midtown'])
            );
    }
}
