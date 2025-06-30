<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::connection($this->connection)->statement('DROP VIEW IF EXISTS schedule_with_bookings');
        DB::connection($this->connection)->statement("
            CREATE OR REPLACE VIEW schedule_with_bookings AS
            WITH date_range AS (SELECT generate_series(
                                           CURRENT_DATE - INTERVAL '1 day',
                                           CURRENT_DATE + INTERVAL '90 days',
                                           INTERVAL '1 day'
                           )::DATE AS date),
            daily_bookings AS (SELECT st.venue_id,
                               b.booking_at::date                                 AS booking_date,
                               SUM(CASE WHEN st.prime_time THEN 1 ELSE 0 END)     AS prime_bookings,
                               SUM(CASE WHEN NOT st.prime_time THEN 1 ELSE 0 END) AS non_prime_bookings
                        FROM bookings b
                                 INNER JOIN schedule_templates st ON st.id = b.schedule_template_id
                        WHERE b.status = 'confirmed'
                        GROUP BY st.venue_id, b.booking_at::date),
            base_schedule AS (SELECT st.venue_id,
                              st.day_of_week,
                              st.start_time,
                              st.party_size,
                              COALESCE(rts.is_available, st.is_available)                       AS is_available,
                              COALESCE(rts.prime_time, st.prime_time)                           AS prime_time,
                              COALESCE(rts.prime_time_fee, st.prime_time_fee)                   AS prime_time_fee,
                              COALESCE(rts.available_tables, st.available_tables)               AS available_tables,
                              COALESCE(rts.price_per_head, st.price_per_head)                   AS price_per_head,
                              COALESCE(rts.minimum_spend_per_guest, st.minimum_spend_per_guest) AS minimum_spend_per_guest,
                              rts.booking_date                                                  as override_date
                       FROM schedule_templates st
                                LEFT JOIN venue_time_slots rts ON rts.schedule_template_id = st.id)
        SELECT st.id                                                               AS id,
               st.id                                                               AS schedule_template_id,
               st.venue_id,
               st.day_of_week,
               st.start_time,
               st.end_time,
               COALESCE(bs.is_available, st.is_available)                          AS is_available,
               COALESCE(bs.available_tables, st.available_tables)                  AS available_tables,
               COALESCE(bs.prime_time, st.prime_time)                              AS prime_time,
               COALESCE(bs.prime_time_fee, st.prime_time_fee)                      AS prime_time_fee,
               COALESCE(bs.price_per_head, st.price_per_head,
                        r.non_prime_fee_per_head)                                  AS price_per_head,
               COALESCE(bs.minimum_spend_per_guest, st.minimum_spend_per_guest, 0) AS minimum_spend_per_guest,
               st.party_size,
               dr.date                                                             AS booking_date,
               (dr.date + st.start_time)::timestamp                                AS booking_at,
               (dr.date + st.start_time)::timestamp                                AS schedule_start,
               (dr.date + st.end_time)::timestamp                                  AS schedule_end,
               CASE
                   WHEN COALESCE(bs.prime_time, st.prime_time) = true
                       AND r.daily_prime_bookings_cap IS NOT NULL
                       AND COALESCE(db.prime_bookings, 0) >= r.daily_prime_bookings_cap
                       THEN 0
                   WHEN COALESCE(bs.prime_time, st.prime_time) = false
                       AND r.daily_non_prime_bookings_cap IS NOT NULL
                       AND COALESCE(db.non_prime_bookings, 0) >= r.daily_non_prime_bookings_cap
                       THEN 0
                   ELSE COALESCE(bs.available_tables, st.available_tables) - COALESCE(b.booked_count, 0)
                   END                                                             AS remaining_tables,
               COALESCE(sp.fee, r.booking_fee)                                     AS effective_fee
        FROM date_range dr
                 JOIN schedule_templates st ON LOWER(TO_CHAR(dr.date, 'FMDay')) = LOWER(st.day_of_week)
                 LEFT JOIN base_schedule bs
                           ON bs.venue_id = st.venue_id
                               AND bs.day_of_week = st.day_of_week
                               AND bs.start_time = st.start_time
                               AND bs.party_size = st.party_size
                               AND (bs.override_date = dr.date OR bs.override_date IS NULL)
                 LEFT JOIN (SELECT schedule_template_id,
                                   DATE(booking_at) as booking_date,
                                   COUNT(*)         AS booked_count
                            FROM bookings
                            WHERE status = 'confirmed'
                            GROUP BY schedule_template_id, DATE(booking_at)) b
                           ON st.id = b.schedule_template_id AND dr.date = b.booking_date
                 LEFT JOIN special_pricing_venues sp ON sp.venue_id = st.venue_id AND sp.date = dr.date
                 LEFT JOIN venues r ON r.id = st.venue_id
                 LEFT JOIN daily_bookings db ON db.venue_id = st.venue_id AND db.booking_date = dr.date;
        ");
    }

    public function down(): void
    {
        DB::connection($this->connection)->statement('DROP VIEW IF EXISTS schedule_with_bookings');
    }
};
