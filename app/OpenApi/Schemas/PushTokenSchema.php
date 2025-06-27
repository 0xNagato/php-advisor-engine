<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class PushTokenSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('PushToken')
            ->properties(
                Schema::string('push_token')
                    ->description('The Expo push notification token for the user')
                    ->example('ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]')
            )
            ->required('push_token');
    }
}
