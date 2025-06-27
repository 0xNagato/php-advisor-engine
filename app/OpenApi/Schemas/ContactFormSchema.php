<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class ContactFormSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('ContactForm')
            ->properties(
                Schema::string('message')
                    ->description('The message content for the contact form')
                    ->example('I have a question about my reservation')
                    ->maxLength(500)
            )
            ->required('message');
    }
}
