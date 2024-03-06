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
        Schema::table('restaurants', function (Blueprint $table) {
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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->integer('payout_concierge')->after('name')->nullable();
            $table->integer('payout_platform')->after('payout_concierge')->nullable();
            $table->integer('payout_charity')->after('payout_platform')->nullable();
        });
    }
};
