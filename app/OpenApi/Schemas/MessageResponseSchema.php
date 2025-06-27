<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class MessageResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('MessageResponse')
            ->properties(
                Schema::string('message')
                    ->description('A message describing the result of the operation')
                    ->example('Operation completed successfully')
            );
    }
}
