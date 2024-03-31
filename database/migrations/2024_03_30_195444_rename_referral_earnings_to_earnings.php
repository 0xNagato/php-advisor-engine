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
        Schema::rename('referral_earnings', 'earnings');

        Schema::table('earnings', static function (Blueprint $table) {
            $table->integer('percentage');
            $table->string('percentage_of');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('earnings', 'referral_earnings');

        Schema::table('referral_earnings', static function (Blueprint $table) {
            $table->dropColumn('percentage');
            $table->dropColumn('percentage_of');
        });
    }
};
