<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('ALTER TABLE bookings AUTO_INCREMENT = 284284;');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
