<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class AppConfigSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('AppConfig')
            ->properties(
                Schema::boolean('bookings_enabled')
                    ->description('Whether bookings are currently enabled')
                    ->example(true),
                Schema::string('bookings_disabled_message')
                    ->description('Message to display when bookings are disabled')
                    ->example('Bookings are temporarily disabled for maintenance'),
                Schema::object('login')
                    ->properties(
                        Schema::string('background_image')
                            ->description('URL of the login page background image')
                            ->example('https://example.com/images/login-bg.jpg'),
                        Schema::string('text_color')
                            ->description('Color of the text on the login page')
                            ->example('#FFFFFF')
                    )
            );
    }
}
