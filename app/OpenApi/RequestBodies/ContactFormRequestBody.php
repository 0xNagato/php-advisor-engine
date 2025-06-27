<?php

namespace App\OpenApi\RequestBodies;

use App\OpenApi\Schemas\ContactFormSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class ContactFormRequestBody extends RequestBodyFactory
{
    public function build(): RequestBody
    {
        return RequestBody::create('ContactForm')
            ->description('Contact form data')
            ->content(
                MediaType::json()->schema(ContactFormSchema::ref())
            );
    }
}
