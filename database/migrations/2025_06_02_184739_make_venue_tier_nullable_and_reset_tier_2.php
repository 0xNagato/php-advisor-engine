<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, make the tier column nullable
        Schema::table('venues', function (Blueprint $table) {
            $table->integer('tier')->nullable()->change();
        });

        // Then update all venues with tier=2 to null
        DB::table('venues')
            ->where('tier', 2)
            ->update(['tier' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, make the tier column non-nullable with default value
        Schema::table('venues', function (Blueprint $table) {
            $table->integer('tier')->default(2)->nullable(false)->change();
        });

        // Then restore tier=2 for venues that had null
        // Note: This won't perfectly restore the original state, but sets a reasonable default
        DB::table('venues')
            ->whereNull('tier')
            ->update(['tier' => 2]);
    }
};
