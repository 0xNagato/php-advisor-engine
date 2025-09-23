<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added missing import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing records that have null region to use default region
        DB::table('venue_collections')
            ->whereNull('region')
            ->update(['region' => 'miami']); // Default to Miami

        // Then make the column required
        Schema::table('venue_collections', function (Blueprint $table) {
            $table->string('region')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_collections', function (Blueprint $table) {
            $table->string('region')->nullable()->change();
        });
    }
};
