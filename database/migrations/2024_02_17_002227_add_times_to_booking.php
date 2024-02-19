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
            // change booking_date to booking_at and type of datetime
            $table->dropColumn('booking_date');
            $table->dateTime('booking_at')->after('guest_phone')->default(now());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_at');
            $table->date('booking_date')->after('guest_phone')->default(now());
        });
    }
};
