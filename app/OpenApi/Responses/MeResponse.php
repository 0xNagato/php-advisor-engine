<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\MeResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class MeResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with the authenticated user\'s profile data')
            ->content(
                MediaType::json()->schema(MeResponseSchema::ref())
            );
    }
}
