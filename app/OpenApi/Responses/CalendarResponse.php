<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\CalendarResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class CalendarResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with availability calendar data')
            ->content(
                MediaType::json()->schema(CalendarResponseSchema::ref())
            );
    }
}
