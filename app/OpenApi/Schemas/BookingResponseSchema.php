<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class BookingResponseSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('BookingResponse')
            ->properties(
                Schema::object('data')
                    ->properties(
                        Schema::boolean('bookings_enabled')
                            ->description('Whether bookings are currently enabled')
                            ->example(true),
                        Schema::string('bookings_disabled_message')
                            ->description('Message to display when bookings are disabled')
                            ->example('Bookings are temporarily disabled for maintenance'),
                        Schema::integer('id')
                            ->description('The booking ID')
                            ->example(123),
                        Schema::integer('guest_count')
                            ->description('The number of guests for the booking')
                            ->example(2),
                        Schema::string('dayDisplay')
                            ->description('Human-readable display of the booking date and time')
                            ->example('Today at 7:00 pm'),
                        Schema::string('status')
                            ->description('The booking status')
                            ->example('pending'),
                        Schema::string('venue')
                            ->description('The venue name')
                            ->example('The Grand Restaurant'),
                        Schema::string('logo')
                            ->description('The venue logo URL')
                            ->example('https://example.com/logo.png')
                            ->nullable(),
                        Schema::string('total')
                            ->description('The total amount with tax')
                            ->example('$100.00'),
                        Schema::string('subtotal')
                            ->description('The subtotal amount without tax')
                            ->example('$90.00'),
                        Schema::string('tax_rate_term')
                            ->description('The tax rate term')
                            ->example('VAT')
                            ->nullable(),
                        Schema::string('tax_amount')
                            ->description('The tax amount')
                            ->example('$10.00')
                            ->nullable(),
                        Schema::string('bookingUrl')
                            ->description('The URL for the booking')
                            ->example('https://example.com/booking/123'),
                        Schema::string('qrCode')
                            ->description('The QR code for the booking')
                            ->example('data:image/png;base64,...'),
                        Schema::string('is_prime')
                            ->description('Whether the booking is a prime booking')
                            ->example('true'),
                        Schema::string('booking_at')
                            ->description('The booking date and time')
                            ->example('2025-07-01 19:00:00'),
                        Schema::string('paymentIntentSecret')
                            ->description('The Stripe payment intent secret for prime bookings')
                            ->example('pi_1234567890_secret_1234567890')
                            ->nullable()
                    )
            );
    }
}
