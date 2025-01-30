<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_templates', function (Blueprint $table) {
            $table->index(['venue_id', 'day_of_week', 'start_time']);
            $table->index(['is_available']);
        });

        Schema::table('venue_time_slots', function (Blueprint $table) {
            $table->index(['booking_date', 'schedule_template_id']);
        });
    }

    public function down(): void
    {
        Schema::table('schedule_templates', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'day_of_week', 'start_time']);
            $table->dropIndex(['is_available']);
        });

        Schema::table('venue_time_slots', function (Blueprint $table) {
            $table->dropIndex(['booking_date', 'schedule_template_id']);
        });
    }
};
