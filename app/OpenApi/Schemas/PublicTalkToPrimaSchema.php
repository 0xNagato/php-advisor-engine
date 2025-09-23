<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class PublicTalkToPrimaSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('PublicTalkToPrima')
            ->properties(
                Schema::string('role')->enum('Hotel / Property', 'Concierge', 'Restaurant', 'Creator / Influencer', 'Other')->example('Restaurant'),
                Schema::string('name')->maxLength(255)->example('Jane Doe'),
                Schema::string('company')->nullable()->maxLength(255)->example('The Grand Hotel'),
                Schema::string('email')->nullable()->example('jane@example.com'),
                Schema::string('phone')->maxLength(255)->example('+1 555 123 4567'),
                Schema::string('city')->nullable()->maxLength(255)->example('New York'),
                Schema::string('preferred_contact_time')->nullable()->maxLength(255)->example('Morning'),
                Schema::string('message')->nullable()->maxLength(2000)->example('I would like more information.')
            )
            ->required('role', 'name', 'phone');
    }
}
