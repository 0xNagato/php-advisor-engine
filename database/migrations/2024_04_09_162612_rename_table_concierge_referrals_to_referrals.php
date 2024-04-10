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
        Schema::rename('concierge_referrals', 'referrals');

        Schema::table('referrals', function (Blueprint $table) {
            $table->string('type')->default('concierge');
            $table->string('referrer_type')->default('concierge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('referrals', 'concierge_referrals');
    }
};
