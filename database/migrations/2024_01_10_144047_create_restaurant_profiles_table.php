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
        Schema::create('restaurant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('restaurant_name');
            $table->string('contact_phone');
            $table->string('website_url');
            $table->text('description');

            $table->jsonb('cuisines');
            $table->jsonb('price_range');

            $table->jsonb('sunday_hours_of_operation');
            $table->jsonb('monday_hours_of_operation');
            $table->jsonb('tuesday_hours_of_operation');
            $table->jsonb('wednesday_hours_of_operation');
            $table->jsonb('thursday_hours_of_operation');
            $table->jsonb('friday_hours_of_operation');
            $table->jsonb('saturday_hours_of_operation');

            $table->string('address_line_1');
            $table->string('address_line_2');
            $table->string('city');
            $table->string('state');
            $table->string('zip');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_profiles');
    }
};
