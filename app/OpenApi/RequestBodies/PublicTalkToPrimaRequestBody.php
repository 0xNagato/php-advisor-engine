<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\PublicTalkToPrimaSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class PublicTalkToPrimaRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('PublicTalkToPrima')
            ->description('Public Talk to PRIMA form data')
            ->content(
                MediaType::json()->schema(PublicTalkToPrimaSchema::ref())
            );
    }
}
