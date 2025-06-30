<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\CuisineListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class CuisineListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of active cuisines')
            ->content(
                MediaType::json()->schema(CuisineListSchema::ref())
            );
    }
}
