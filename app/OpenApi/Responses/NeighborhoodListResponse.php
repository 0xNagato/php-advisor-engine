<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\NeighborhoodListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class NeighborhoodListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of active neighborhoods')
            ->content(
                MediaType::json()->schema(NeighborhoodListSchema::ref())
            );
    }
}
