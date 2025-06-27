<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\VipSessionAnalyticsResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class VipSessionAnalyticsResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with VIP session analytics data')
            ->content(
                MediaType::json()->schema(VipSessionAnalyticsResponseSchema::ref())
            );
    }
}
