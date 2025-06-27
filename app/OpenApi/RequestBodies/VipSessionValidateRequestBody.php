<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\SessionTokenSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class VipSessionValidateRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('VipSessionValidate')
            ->description('Session token data')
            ->content(
                MediaType::json()->schema(SessionTokenSchema::ref())
            );
    }
}
