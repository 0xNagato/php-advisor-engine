<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\VipCodeSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class VipSessionCreateRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('VipSessionCreate')
            ->description('VIP code data')
            ->content(
                MediaType::json()->schema(VipCodeSchema::ref())
            );
    }
}
