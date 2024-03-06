<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('restaurant_earnings')->default(0);
            $table->integer('concierge_earnings')->default(0);
            $table->integer('charity_earnings')->default(0);
            $table->integer('platform_earnings')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('restaurant_earnings');
            $table->dropColumn('concierge_earnings');
            $table->dropColumn('charity_earnings');
            $table->dropColumn('platform_earnings');
        });
    }
};
