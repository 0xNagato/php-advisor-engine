<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class SpecialtyListSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('SpecialtyList')
            ->properties(
                Schema::object('data')
                    ->additionalProperties(Schema::string())
                    ->description('A mapping of specialty IDs to specialty names')
                    ->example([
                        'waterfront' => 'Waterfront', 'sunset_view' => 'Sunset view',
                        'traditional_ibiza' => 'Traditional Ibiza',
                    ])
            );
    }
}
