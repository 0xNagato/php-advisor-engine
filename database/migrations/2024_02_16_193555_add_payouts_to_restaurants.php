<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('payout_restaurant')->default(60);
            $table->integer('payout_charity')->default(5);
            $table->integer('payout_concierge')->default(15);
            $table->integer('payout_platform')->default(20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payout_restaurant');
            $table->dropColumn('payout_charity');
            $table->dropColumn('payout_concierge');
            $table->dropColumn('payout_platform');
        });
    }
};
