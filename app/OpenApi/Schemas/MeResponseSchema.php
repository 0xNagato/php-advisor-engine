<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class MeResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('MeResponse')
            ->properties(
                Schema::boolean('success')->example(true),
                Schema::object('data')
                    ->properties(
                        Schema::object('user')
                            ->properties(
                                Schema::integer('id')->description('The user ID')->example(1),
                                Schema::string('role')->description('The user\'s main role')->example('concierge'),
                                Schema::string('email')->description('The user\'s email address')->example('user@example.com'),
                                Schema::string('name')->description('The user\'s name')->example('John Doe'),
                                Schema::string('avatar')->description('The user\'s avatar URL')->example('https://example.com/avatar.jpg')->nullable(),
                                Schema::string('timezone')->description('The user\'s timezone')->example('America/New_York'),
                                Schema::string('region')->description('The user\'s region')->example('new_york')->nullable()
                            )
                    )
            );
    }
}
