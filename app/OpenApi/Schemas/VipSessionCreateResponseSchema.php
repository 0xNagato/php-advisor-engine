<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VipSessionCreateResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VipSessionCreateResponse')
            ->properties(
                Schema::boolean('success')->example(true),
                Schema::object('data')
                    ->properties(
                        Schema::string('session_token')->example('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
                        Schema::string('expires_at')->format('date-time')->example('2025-07-01T12:00:00Z'),
                        Schema::boolean('is_demo')->example(false),
                        Schema::string('demo_message')->nullable()->example('This is a demo session'),
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
                            ->nullable()
                    )
            );
    }
}
