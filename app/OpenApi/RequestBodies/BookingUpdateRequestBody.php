<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\BookingUpdateSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class BookingUpdateRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('BookingUpdate')
            ->description('Booking update data')
            ->content(
                MediaType::json()->schema(BookingUpdateSchema::ref())
            );
    }
}
