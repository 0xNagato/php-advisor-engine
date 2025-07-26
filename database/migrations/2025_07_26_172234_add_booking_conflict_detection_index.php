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
        Schema::table('bookings', function (Blueprint $table) {
            // Add compound index for booking conflict detection query
            // This optimizes the query in CheckCustomerHasConflictingNonPrimeBooking
            $table->index(['guest_phone', 'is_prime', 'status', 'booking_at'], 'idx_bookings_conflict_detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_conflict_detection');
        });
    }
};
