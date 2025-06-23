<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingEmailInvoiceResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingEmailInvoiceResponse')
            ->properties(
                Schema::string('message')
                    ->description('A message describing the result of the operation')
                    ->example('Operation completed successfully'),
                Schema::array('data')
                    ->properties(
                        Schema::string('email')->description('email')->example('john.doe@example.com'),
                    )
            );
    }
}
