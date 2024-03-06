<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payout_restaurant');
            $table->dropColumn('payout_concierge');
            $table->dropColumn('payout_platform');
            $table->dropColumn('payout_charity');
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
            $table->integer('payout_restaurant')->after('stripe_charge_id')->nullable();
            $table->integer('payout_concierge')->after('payout_restaurant')->nullable();
            $table->integer('payout_platform')->after('payout_concierge')->nullable();
            $table->integer('payout_charity')->after('payout_platform')->nullable();
        });
    }
};
