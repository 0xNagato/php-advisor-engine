<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\BookingEmailInvoiceResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class BookingEmailInvoiceResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()->description('Successful response for emailing a booking invoice')
            ->content(MediaType::json()->schema(BookingEmailInvoiceResponseSchema::ref()));
    }
}
