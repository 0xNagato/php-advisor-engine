<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class RoleProfileListSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('RoleProfileList')
            ->properties(
                Schema::array('profiles')
                    ->items(
                        Schema::object()
                            ->properties(
                                Schema::integer('id')
                                    ->description('The role profile ID')
                                    ->example(1),
                                Schema::string('name')
                                    ->description('The role profile name')
                                    ->example('Admin Profile'),
                                Schema::string('role')
                                    ->description('The role name')
                                    ->example('admin'),
                                Schema::boolean('is_active')
                                    ->description('Whether the role profile is active')
                                    ->example(true)
                            )
                    )
                    ->description('List of role profiles for the authenticated user')
            );
    }
}
