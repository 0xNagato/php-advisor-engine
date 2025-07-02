<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\BookingCompleteSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class BookingCompleteRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create()
            ->description('Booking complete data')
            ->content(
                MediaType::json()->schema(BookingCompleteSchema::ref())
            );
    }
}
