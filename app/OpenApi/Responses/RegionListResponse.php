<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\RegionListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class RegionListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of active regions')
            ->content(
                MediaType::json()->schema(RegionListSchema::ref())
            );
    }
}
