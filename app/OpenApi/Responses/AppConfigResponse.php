<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\AppConfigSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class AppConfigResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with application configuration')
            ->content(
                MediaType::json()->schema(AppConfigSchema::ref())
            );
    }
}
