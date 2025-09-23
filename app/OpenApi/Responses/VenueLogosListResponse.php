<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VenueLogosListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class VenueLogosListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with venue logos data for web component')
            ->content(
                MediaType::json()->schema(VenueLogosListSchema::ref())
            );
    }
}
