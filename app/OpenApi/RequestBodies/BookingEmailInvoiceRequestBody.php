<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\BookingEmailInvoiceSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class BookingEmailInvoiceRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create()
            ->description('Request body for emailing a booking invoice')
            ->content(
                MediaType::json()->schema(BookingEmailInvoiceSchema::ref())
            );
    }
}
