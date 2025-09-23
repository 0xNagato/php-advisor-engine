<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class VenueLogosListSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        $venueItemSchema = Schema::object('VenueLogoItem')
            ->properties(
                Schema::integer('id')->description('Venue ID'),
                Schema::string('name')->description('Venue name'),
                Schema::string('logo_path')->nullable()->description('Full URL to venue logo')
            );

        return Schema::object('VenueLogosList')
            ->properties(
                Schema::array('first_row')
                    ->items($venueItemSchema)
                    ->description('First row of venue logos for display'),
                Schema::array('second_row')
                    ->items($venueItemSchema)
                    ->description('Second row of venue logos for display'),
                Schema::integer('total_venues')
                    ->description('Total number of venues returned'),
                Schema::string('generated_at')
                    ->format('date-time')
                    ->description('Timestamp when data was generated')
            );
    }
}