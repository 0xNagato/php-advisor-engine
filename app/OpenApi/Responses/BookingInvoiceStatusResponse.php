<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\BookingInvoiceStatusSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class BookingInvoiceStatusResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with booking invoice status')
            ->content(
                MediaType::json()->schema(
                    BookingInvoiceStatusSchema::ref()
                )
            );
    }
}
