<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_modification_requests', function (Blueprint $table) {
            $table->after('requested_time', function (Blueprint $table) {
                $table->date('original_booking_at')->nullable();
                $table->date('request_booking_at')->nullable();
            });
        });
    }

    public function down(): void
    {
        Schema::table('booking_modification_requests', function (Blueprint $table) {
            $table->dropColumn(['original_booking_at', 'request_booking_at']);
        });
    }
};
