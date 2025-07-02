<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VenueDetailsSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class ShowVenueResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with booking details')
            ->content(
                MediaType::json()->schema(VenueDetailsSchema::ref())
            );
    }
}
