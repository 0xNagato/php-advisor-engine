<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Laravel will not run this migration inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        // Optional clean-up (drop if already exists)
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_template_date;');
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_venue_date_available;');
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_venue_date_party_start;');
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_venue_start_size_date;');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_schedule_mv_unique_combined;');

        DB::statement('
            CREATE INDEX CONCURRENTLY idx_schedule_mv_template_date
            ON schedule_with_bookings_mv (schedule_template_id, booking_date)
        ');

        DB::statement('
            CREATE INDEX CONCURRENTLY idx_schedule_mv_venue_date_available
            ON schedule_with_bookings_mv (venue_id, booking_date, is_available)
        ');

        DB::statement('
            CREATE INDEX CONCURRENTLY idx_schedule_mv_venue_date_party_start
            ON schedule_with_bookings_mv (venue_id, booking_date, party_size, start_time)
        ');

        DB::statement('
            CREATE INDEX CONCURRENTLY idx_schedule_mv_venue_start_size_date
            ON schedule_with_bookings_mv (venue_id, start_time, party_size, booking_date)
        ');

        DB::statement('
            CREATE UNIQUE INDEX CONCURRENTLY idx_schedule_mv_unique_combined
            ON schedule_with_bookings_mv (schedule_template_id, booking_date, start_time, party_size)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_template_date;');
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_venue_date_available;');
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_venue_date_party_start;');
        DB::statement('DROP INDEX IF EXISTS idx_schedule_mv_venue_start_size_date;');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_schedule_mv_unique_combined;');
    }
};
