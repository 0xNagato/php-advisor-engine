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
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_concierge_id')->nullable()->after('concierge_id');
            $table->foreign('partner_concierge_id')->references('id')->on('partners');

            $table->unsignedBigInteger('partner_restaurant_id')->nullable()->after('partner_concierge_id');
            $table->foreign('partner_restaurant_id')->references('id')->on('partners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['partner_concierge_id']);
            $table->dropColumn('partner_concierge_id');

            $table->dropForeign(['partner_restaurant_id']);
            $table->dropColumn('partner_restaurant_id');
        });
    }
};
