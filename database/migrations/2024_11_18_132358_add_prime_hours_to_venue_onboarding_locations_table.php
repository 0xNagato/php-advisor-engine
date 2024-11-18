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
        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->json('prime_hours')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->dropColumn('prime_hours');
        });
    }
};
