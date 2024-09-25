<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['confirmed_at', 'created_at']);
        });

        Schema::table('schedule_templates', function (Blueprint $table) {
            $table->index(['id', 'venue_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['confirmed_at', 'created_at']);
        });

        Schema::table('schedule_templates', function (Blueprint $table) {
            $table->dropIndex(['id', 'venue_id']);
        });
    }
};
