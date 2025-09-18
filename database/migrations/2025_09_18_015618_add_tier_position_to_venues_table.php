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
        Schema::table('venues', function (Blueprint $table) {
            $table->integer('tier_position')->nullable()->after('tier');
            $table->index(['region', 'tier', 'tier_position'], 'venues_region_tier_position_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex('venues_region_tier_position_idx');
            $table->dropColumn('tier_position');
        });
    }
};
