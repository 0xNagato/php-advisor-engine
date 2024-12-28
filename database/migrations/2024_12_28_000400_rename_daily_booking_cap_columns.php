<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            if (Schema::hasColumn('venues', 'daily_booking_cap') && ! Schema::hasColumn('venues', 'daily_prime_bookings_cap')) {
                $table->renameColumn('daily_booking_cap', 'daily_prime_bookings_cap');
            }

            if (! Schema::hasColumn('venues', 'daily_non_prime_bookings_cap')) {
                $table->integer('daily_non_prime_bookings_cap')->nullable()->after('daily_prime_bookings_cap')
                    ->comment('Maximum number of non-prime bookings allowed per day (null for no limit)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            if (Schema::hasColumn('venues', 'daily_prime_bookings_cap') && ! Schema::hasColumn('venues', 'daily_booking_cap')) {
                $table->renameColumn('daily_prime_bookings_cap', 'daily_booking_cap');
            }

            if (Schema::hasColumn('venues', 'daily_non_prime_bookings_cap')) {
                $table->dropColumn('daily_non_prime_bookings_cap');
            }
        });
    }
};
