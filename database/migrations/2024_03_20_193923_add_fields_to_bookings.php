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
            $table->dateTime('clicked_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('concierge_referral_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('clicked_at');
            $table->dropColumn('confirmed_at');
            $table->dropColumn('concierge_referral_type');
        });
    }
};
