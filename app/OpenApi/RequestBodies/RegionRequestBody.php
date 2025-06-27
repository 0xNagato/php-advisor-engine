<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\RegionSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class RegionRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('Region')
            ->description('Region data')
            ->content(
                MediaType::json()->schema(RegionSchema::ref())
            );
    }
}
