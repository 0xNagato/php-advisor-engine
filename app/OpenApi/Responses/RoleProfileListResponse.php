<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\RoleProfileListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class RoleProfileListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of role profiles for the authenticated user')
            ->content(
                MediaType::json()->schema(RoleProfileListSchema::ref())
            );
    }
}
