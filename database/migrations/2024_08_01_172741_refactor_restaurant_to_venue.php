<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public array $tables = [
        'schedule_templates',
        'special_pricing_restaurants',
        'special_requests',
    ];

    public function up(): void
    {
        Schema::dropIfExists('earning_errors');

        Schema::table('restaurants', static function (Blueprint $table) {
            $table->renameColumn('restaurant_logo_path', 'logo_path');
            $table->renameColumn('payout_restaurant', 'payout_venue');
        });

        Schema::table('bookings', static function (Blueprint $table) {
            $table->renameColumn('restaurant_earnings', 'venue_earnings');
            $table->renameColumn('restaurant_confirmed_at', 'venue_confirmed_at');
            $table->renameColumn('resent_restaurant_confirmation_at', 'resent_venue_confirmation_at');
            $table->renameColumn('partner_restaurant_id', 'partner_venue_id');
            $table->renameColumn('partner_restaurant_fee', 'partner_venue_fee');
        });

        foreach ($this->tables as $table) {
            Schema::table($table, static function (Blueprint $table) {
                $table->renameColumn('restaurant_id', 'venue_id');
            });
        }

        Schema::rename('restaurants', 'venues');
        Schema::rename('restaurant_time_slots', 'venue_time_slots');
        Schema::rename('special_pricing_restaurants', 'special_pricing_venues');
    }

    public function down(): void
    {
        Schema::table('venues', static function (Blueprint $table) {
            $table->renameColumn('name', 'name');
        });

        foreach ($this->tables as $table) {
            Schema::table($table, static function (Blueprint $table) {
                $table->renameColumn('venue_id', 'restaurant_id');
            });
        }

        Schema::rename('venues', 'venues');
        Schema::rename('venue_time_slots', 'venue_time_slots');
        Schema::rename('special_pricing_venues', 'special_pricing_venues');
    }
};
