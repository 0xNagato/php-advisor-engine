<?php

namespace App\OpenApi\Responses;

use App\OpenApi\Schemas\SpecialtyListSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class SpecialtyListResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::ok()
            ->description('Successful response with a list of active specialties')
            ->content(
                MediaType::json()->schema(SpecialtyListSchema::ref())
            );
    }
}
