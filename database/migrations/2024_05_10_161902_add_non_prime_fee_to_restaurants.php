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
        Schema::table('restaurants', static function (Blueprint $table) {
            $table->integer('non_prime_fee_per_head')->default(10)->after('increment_fee');
            $table->enum('non_prime_type', ['free', 'paid'])->default('paid')->after('non_prime_fee_per_head');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', static function (Blueprint $table) {
            $table->dropColumn('non_prime_fee_per_head');
            $table->dropColumn('non_prime_type');
        });
    }
};
