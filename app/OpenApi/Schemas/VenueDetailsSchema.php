<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VenueDetailsSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object('VenueDetails')
            ->properties(
                Schema::object('data')
                    ->properties(
                        Schema::integer('id')
                            ->description('The unique identifier of the venue')
                            ->example(1),
                        Schema::string('name')
                            ->description('The name of the venue')
                            ->example('Restaurant A'),
                        Schema::string('slug')
                            ->description('The slug of the venue')
                            ->example('restaurant-a'),
                        Schema::string('address')
                            ->description('The address of the venue')
                            ->example('123 Main St, Miami, FL'),
                        Schema::string('description')
                            ->description('A description of the venue')
                            ->example('A beautiful restaurant...'),
                        Schema::array('images')
                            ->items(Schema::string()->example('path/to/image1.jpg'))
                            ->description('List of image paths for the venue'),
                        Schema::string('logo')
                            ->description('The logo of the venue')
                            ->example('path/to/logo.png'),
                        Schema::array('cuisines')
                            ->items(Schema::string()->example('italian'))
                            ->description('List of cuisines offered by the venue'),
                        Schema::array('specialty')
                            ->items(Schema::string()->example('waterfront'))
                            ->description('Special features of the venue'),
                        Schema::string('neighborhood')
                            ->description('The neighborhood where the venue is located')
                            ->example('South Beach'),
                        Schema::string('region')
                            ->description('The region where the venue is located')
                            ->example('miami'),
                        Schema::string('status')
                            ->description('The status of the venue')
                            ->example('active'),
                        Schema::string('formatted_location')
                            ->description('Formatted location of the venue')
                            ->example('South Beach, Miami')
                    )
            );
    }
}
