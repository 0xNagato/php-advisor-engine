<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class NoContentResponse extends ResponseFactory
{
    public function build(): Response
    {
        return Response::create('NoContent')
            ->statusCode(204)
            ->description('The request has been successfully processed, but there is no content to return');
    }
}
