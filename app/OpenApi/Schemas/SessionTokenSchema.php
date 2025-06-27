<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class SessionTokenSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('SessionToken')
            ->properties(
                Schema::string('session_token')->description('The session token to validate')->example('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9')
            )
            ->required('session_token');
    }
}
