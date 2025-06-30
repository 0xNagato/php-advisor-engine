<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VipSessionCreateResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class VipSessionCreateResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with VIP session creation details')
            ->content(
                MediaType::json()->schema(VipSessionCreateResponseSchema::ref())
            );
    }
}
