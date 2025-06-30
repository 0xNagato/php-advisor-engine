<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\PushTokenSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class PushTokenRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('PushToken')
            ->description('Push token data')
            ->content(
                MediaType::json()->schema(PushTokenSchema::ref())
            );
    }
}
