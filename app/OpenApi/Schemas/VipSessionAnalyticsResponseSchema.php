<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VipSessionAnalyticsResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VipSessionAnalyticsResponse')
            ->properties(
                Schema::boolean('success')->example(true),
                Schema::object('data')
                    ->properties(
                        Schema::integer('total_sessions')->example(100),
                        Schema::integer('active_sessions')->example(25),
                        Schema::integer('expired_sessions')->example(75),
                        Schema::object('session_creation_rate')
                            ->properties(
                                Schema::integer('last_24h')->example(5),
                                Schema::integer('last_7d')->example(20),
                                Schema::integer('last_30d')->example(50)
                            )
                    )
            );
    }
}
