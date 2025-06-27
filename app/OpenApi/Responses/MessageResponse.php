<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\MessageResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class MessageResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a message')
            ->content(
                MediaType::json()->schema(MessageResponseSchema::ref())
            );
    }
}
