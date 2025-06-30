<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingUpdateSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingUpdate')
            ->properties(
                Schema::string('first_name')
                    ->description('The first name of the guest')
                    ->example('John')
                    ->maxLength(255),
                Schema::string('last_name')
                    ->description('The last name of the guest')
                    ->example('Doe')
                    ->maxLength(255),
                Schema::string('phone')
                    ->description('The phone number of the guest')
                    ->example('+1234567890'),
                Schema::string('email')
                    ->description('The email address of the guest')
                    ->format('email')
                    ->example('john.doe@example.com')
                    ->nullable(),
                Schema::string('notes')
                    ->description('Additional notes for the booking')
                    ->example('Allergic to nuts')
                    ->maxLength(1000)
                    ->nullable(),
                Schema::string('bookingUrl')
                    ->description('The URL for the booking payment form')
                    ->format('url')
                    ->example('https://example.com/booking/payment/123')
            )
            ->required('first_name', 'last_name', 'phone', 'bookingUrl');
    }
}
