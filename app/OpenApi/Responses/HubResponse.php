<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\HubResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class HubResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with venue schedules for a specific venue')
            ->content(
                MediaType::json()->schema(HubResponseSchema::ref())
            );
    }
}
