<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->index(['booking_id', 'user_id', 'type'], 'idx_earnings_booking_id_user_id_type');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['confirmed_at', 'booking_at'], 'idx_bookings_confirmed_at_booking_at');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->index('user_id', 'idx_venues_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropIndex('idx_earnings_booking_id_user_id_type');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_confirmed_at_booking_at');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex('idx_venues_user_id');
        });
    }
};
