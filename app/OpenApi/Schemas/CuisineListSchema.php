<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class CuisineListSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('CuisineList')
            ->properties(
                Schema::object('data')
                    ->additionalProperties(Schema::string())
                    ->description('A mapping of cuisine IDs to cuisine names')
                    ->example(['italian' => 'Italian', 'japanese' => 'Japanese', 'mexican' => 'Mexican'])
            );
    }
}
