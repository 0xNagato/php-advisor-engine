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
            $table->foreignId('venue_group_id')->nullable()->after('venue_onboarding_id')->constrained('venue_groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->dropForeign(['venue_group_id']);
            $table->dropColumn('venue_group_id');
        });
    }
};
