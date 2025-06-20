<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingCompleteSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingComplete')
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
                Schema::string('payment_intent_id')
                    ->description('Stripe payment intent ID for prime bookings')
                    ->example('pi_1J2Y3Z4X5Y6Z7A8B9C0D1E2F')
                    ->maxLength(1000)
                    ->nullable(),
                Schema::string('bookingUrl')
                    ->description('Referral Code')
                    ->format('r')
                    ->example('REF12345')
            );
    }
}
