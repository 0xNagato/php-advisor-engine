<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VipSessionValidateResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VipSessionValidateResponse')
            ->properties(
                Schema::boolean('success')->example(true),
                Schema::object('data')
                    ->properties(
                        Schema::boolean('valid')->example(true),
                        Schema::boolean('is_demo')->example(false),
                        Schema::object('session')
                            ->properties(
                                Schema::integer('id')->example(1),
                                Schema::string('expires_at')->format('date-time')->example('2025-07-01T12:00:00Z')
                            )
                            ->nullable(),
                        Schema::object('vip_code')
                            ->properties(
                                Schema::integer('id')->example(1),
                                Schema::string('code')->example('ABC123'),
                                Schema::object('concierge')
                                    ->properties(
                                        Schema::integer('id')->example(1),
                                        Schema::string('name')->example('John Doe'),
                                        Schema::string('hotel_name')->example('Grand Hotel')
                                    )
                            )
                            ->nullable(),
                        Schema::string('message')->nullable()->example('Invalid or expired session token')
                    )
            );
    }
}
