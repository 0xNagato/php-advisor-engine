<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VipSessionValidateResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class VipSessionValidateResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with VIP session validation details')
            ->content(
                MediaType::json()->schema(VipSessionValidateResponseSchema::ref())
            );
    }
}
