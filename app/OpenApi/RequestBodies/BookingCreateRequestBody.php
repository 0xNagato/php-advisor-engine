<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\BookingCreateSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class BookingCreateRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('BookingCreate')
            ->description('Booking creation data')
            ->content(
                MediaType::json()->schema(BookingCreateSchema::ref())
            );
    }
}
