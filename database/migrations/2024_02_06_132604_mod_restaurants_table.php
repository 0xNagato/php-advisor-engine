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
        Schema::table('restaurant_profiles', function (Blueprint $table) {
            $table->string('address_line_1')->nullable()->change();
            $table->string('address_line_2')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('state')->nullable()->change();
            $table->string('zip')->nullable()->change();
            $table->string('website_url')->nullable()->change();
            $table->string('description')->nullable()->change();
            $table->string('price_range')->change();
            $table->integer('payout_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_profiles', function (Blueprint $table) {
            $table->string('website_url')->nullable(false)->change();
            $table->string('address_line_1')->nullable(false)->change();
            $table->string('address_line_2')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('state')->nullable(false)->change();
            $table->string('zip')->nullable(false)->change();
            $table->string('description')->nullable(false)->change();
            $table->jsonb('price_range')->change();
            $table->dropColumn('payout_percentage');
        });
    }
};
