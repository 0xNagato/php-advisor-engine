<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VenueLogosCacheClearSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class VenueLogosCacheClearResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Cache cleared successfully')
            ->content(
                MediaType::json()->schema(VenueLogosCacheClearSchema::ref())
            );
    }
}