<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('venue_time_slots', function (Blueprint $table) {
            $table->boolean('is_available')->default(true);
            $table->integer('available_tables')->default(0);
            $table->integer('price_per_head')->nullable();
            $table->integer('minimum_spend_per_guest')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_time_slots', function (Blueprint $table) {
            $table->dropColumn([
                'is_available',
                'available_tables',
                'price_per_head',
                'minimum_spend_per_guest',
            ]);
        });
    }
};
