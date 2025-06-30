<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VenueListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class VenueListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of available venues')
            ->content(
                MediaType::json()->schema(VenueListSchema::ref())
            );
    }
}
