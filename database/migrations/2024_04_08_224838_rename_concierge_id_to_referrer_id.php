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
        Schema::table('concierge_referrals', function (Blueprint $table) {
            $table->dropForeign(['concierge_id']);
            $table->renameColumn('concierge_id', 'referrer_id');
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concierge_referrals', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->renameColumn('referrer_id', 'concierge_id');
            $table->foreign('concierge_id')->references('id')->on('concierge')->onDelete('cascade');
        });
    }
};
