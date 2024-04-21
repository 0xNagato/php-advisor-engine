<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        DB::statement("
            CREATE VIEW available_schedules AS
            SELECT s.*,
                   (s.available_tables - (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.id AND b.status = 'confirmed')) AS remaining_tables
            FROM schedules s
            WHERE s.is_available = 1 AND
                  (s.available_tables - (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.id AND b.status = 'confirmed')) > 0
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS AvailableSchedules");
    }
};
