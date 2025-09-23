<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VenueLogosCacheClearSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VenueLogosCacheClear')
            ->properties(
                Schema::string('message')
                    ->description('Success message'),
                Schema::string('cleared_at')
                    ->format('date-time')
                    ->description('Timestamp when cache was cleared')
            );
    }
}
