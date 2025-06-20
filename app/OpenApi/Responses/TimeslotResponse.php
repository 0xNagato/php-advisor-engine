<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\TimeslotListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class TimeslotResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of available or unavailable timeslots')
            ->content(
                MediaType::json()->schema(TimeslotListSchema::ref())
            );
    }
}
