<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingCompleteResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingCompleteResponse')
            ->properties(
                Schema::string('message')
                    ->description('A message describing the result of the operation')
                    ->example('Booking completed successfully'),
                Schema::object('data')
                    ->properties(
                        Schema::object('booking')
                            ->properties(
                                Schema::string('id')->description('Booking ID')->example('12345'),
                                Schema::string('status')->description('Booking status')->example('confirmed'),
                                Schema::string('guest_first_name')->description('Guest\'s first name')->example('John'),
                                Schema::string('guest_last_name')->description('Guest\'s last name')->example('Doe'),
                                Schema::string('guest_phone')->description('Guest\'s phone number')->example('+123456789'),
                                Schema::string('guest_email')->description('Guest\'s email')->example('john.doe@example.com'),
                                Schema::string('notes')->description('Additional notes')->nullable()->example('No special requests'),
                                Schema::string('invoice_download_url')->description('URL to download the invoice')->nullable()->example('https://example.com/invoice/12345')
                            ),
                        Schema::object('result')
                            ->properties(
                                Schema::string('payment_status')->description('Payment status')->example('success'),
                                Schema::string('payment_intent_id')->description('Payment intent ID')->example('pi_123456789')
                            )
                    )
            );
    }
}
