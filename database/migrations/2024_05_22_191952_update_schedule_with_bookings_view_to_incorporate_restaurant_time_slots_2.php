<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing view if it exists
        DB::statement('DROP VIEW IF EXISTS schedule_with_bookings');

        // Create the new view with the updated definition
        DB::statement("
            CREATE OR REPLACE VIEW schedule_with_bookings AS
            WITH RECURSIVE date_range AS (
                SELECT CURDATE() - INTERVAL 1 DAY AS date
                UNION ALL
                SELECT DATE_ADD(date, INTERVAL 1 DAY)
                FROM date_range
                WHERE date < CURDATE() + INTERVAL 30 DAY
            )
            SELECT
                st.id AS id,
                st.id AS schedule_template_id,
                st.restaurant_id,
                st.day_of_week,
                st.start_time,
                st.end_time,
                st.is_available,
                st.available_tables,
                COALESCE(rts.prime_time, st.prime_time) AS prime_time,
                st.prime_time_fee,
                st.party_size,
                dr.date AS booking_date,
                DATE_FORMAT(TIMESTAMP(CONCAT(DATE_FORMAT(dr.date, '%Y-%m-%d'), ' ', TIME_FORMAT(st.start_time, '%H:%i:%s'))), '%Y-%m-%d %H:%i:%s') AS booking_at,
                DATE_FORMAT(TIMESTAMP(CONCAT(DATE_FORMAT(dr.date, '%Y-%m-%d'), ' ', TIME_FORMAT(st.start_time, '%H:%i:%s'))), '%Y-%m-%d %H:%i:%s') AS schedule_start,
                DATE_FORMAT(TIMESTAMP(CONCAT(DATE_FORMAT(dr.date, '%Y-%m-%d'), ' ', TIME_FORMAT(st.end_time, '%H:%i:%s'))), '%Y-%m-%d %H:%i:%s') AS schedule_end,
                st.available_tables - IFNULL(b.booked_count, 0) AS remaining_tables,
                COALESCE(sp.fee, r.booking_fee) AS effective_fee
            FROM
                date_range dr
            JOIN
                schedule_templates st ON DAYNAME(dr.date) = st.day_of_week
            LEFT JOIN (
                SELECT
                    schedule_template_id,
                    COUNT(*) AS booked_count
                FROM bookings
                WHERE status = 'confirmed'
                GROUP BY schedule_template_id
            ) b ON st.id = b.schedule_template_id
            LEFT JOIN special_pricing_restaurants sp ON sp.restaurant_id = st.restaurant_id AND sp.date = dr.date
            LEFT JOIN restaurants r ON r.id = st.restaurant_id
            LEFT JOIN restaurant_time_slots rts ON rts.schedule_template_id = st.id AND rts.booking_date = dr.date
            WHERE st.is_available = 1;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the view when rolling back
        DB::statement('DROP VIEW IF EXISTS schedule_with_bookings');
    }
};
