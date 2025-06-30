<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VipCodeSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VipCode')
            ->properties(
                Schema::string('vip_code')->description('The VIP code to create a session')->example('ABC123')->minLength(4)->maxLength(12)
            )
            ->required('vip_code');
    }
}
