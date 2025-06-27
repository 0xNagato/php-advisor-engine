<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingInvoiceStatusSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingInvoiceStatus')
            ->properties(
                Schema::object('status')
                    ->additionalProperties(Schema::string())
                    ->description('Status of the booking invoice')
                    ->example('processing'),
                Schema::string('message')
                    ->description('A message describing the result of the operation')
                    ->example('Operation completed successfully')
            );
    }
}
