<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS schedule_with_bookings');

        DB::statement("
            CREATE OR REPLACE VIEW schedule_with_bookings AS
            WITH RECURSIVE date_range AS (
                SELECT CURDATE() - INTERVAL 1 DAY AS date
                UNION ALL
                SELECT DATE_ADD(date, INTERVAL 1 DAY)
                FROM date_range
                WHERE date < CURDATE() + INTERVAL 30 DAY
            ),
            daily_bookings AS (
                SELECT
                    st.venue_id,
                    DATE(b.booking_at) as booking_date,
                    COUNT(*) as total_bookings
                FROM bookings b
                JOIN schedule_templates st ON st.id = b.schedule_template_id
                WHERE b.status = 'confirmed'
                GROUP BY st.venue_id, DATE(b.booking_at)
            )
            SELECT
                st.id AS id,
                st.id AS schedule_template_id,
                st.venue_id,
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
                CASE
                    WHEN r.daily_booking_cap IS NOT NULL
                        AND COALESCE(db.total_bookings, 0) >= r.daily_booking_cap
                    THEN 0
                    ELSE st.available_tables - IFNULL(b.booked_count, 0)
                END AS remaining_tables,
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
            LEFT JOIN special_pricing_venues sp ON sp.venue_id = st.venue_id AND sp.date = dr.date
            LEFT JOIN venues r ON r.id = st.venue_id
            LEFT JOIN venue_time_slots rts ON rts.schedule_template_id = st.id AND rts.booking_date = dr.date
            LEFT JOIN daily_bookings db ON db.venue_id = st.venue_id AND db.booking_date = dr.date;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS schedule_with_bookings');

        // Here we reference the previous migration that created the original view
        DB::statement(file_get_contents(database_path('migrations/2024_08_01_180400_update_schedule_with_bookings_view_rename_restaurant_venue.php')));
    }
};
