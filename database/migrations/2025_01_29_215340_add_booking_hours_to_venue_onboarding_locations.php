<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->json('booking_hours')->nullable()->after('prime_hours');
        });
    }

    public function down(): void
    {
        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->dropColumn('booking_hours');
        });
    }
};
