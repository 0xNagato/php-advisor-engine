<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->integer('daily_booking_cap')->nullable()->after('cutoff_time')
                ->comment('Maximum number of bookings allowed per day (null for no limit)');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('daily_booking_cap');
        });
    }
};
