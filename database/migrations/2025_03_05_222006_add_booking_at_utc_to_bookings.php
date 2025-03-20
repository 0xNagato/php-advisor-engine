<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->datetime('booking_at_utc')->nullable()->after('booking_at');
        });

        // Set session timezone to UTC instead of global
        DB::statement('SET time_zone = "+00:00"');

        // Convert booking_at to UTC based on venue timezone
        DB::statement('
            UPDATE bookings b
            INNER JOIN schedule_templates st ON b.schedule_template_id = st.id
            INNER JOIN venues v ON st.venue_id = v.id
            SET b.booking_at_utc =
                CASE
                    WHEN v.timezone = "UTC" OR v.timezone = "+00:00" THEN b.booking_at
                    ELSE CONVERT_TZ(b.booking_at, COALESCE(v.timezone, "+00:00"), "+00:00")
                END
            WHERE b.booking_at IS NOT NULL
        ');

        // Handle any bookings without a valid schedule_template relationship (fallback to UTC)
        DB::statement('
            UPDATE bookings
            SET booking_at_utc = booking_at
            WHERE booking_at IS NOT NULL AND booking_at_utc IS NULL
        ');

        // Verify the conversion worked by checking if any records still have identical booking_at and booking_at_utc
        $identicalCount = DB::selectOne('
            SELECT COUNT(*) as count FROM bookings
            WHERE booking_at = booking_at_utc AND booking_at IS NOT NULL
        ')->count;

        if ($identicalCount > 0) {
            // Try alternative approach with PHP for timezones that MySQL might not support
            $venues = DB::select('
                SELECT v.id, v.timezone
                FROM venues v
                WHERE v.timezone IS NOT NULL AND v.timezone != "UTC" AND v.timezone != "+00:00"
            ');

            foreach ($venues as $venue) {
                $bookings = DB::select('
                    SELECT b.id, b.booking_at
                    FROM bookings b
                    INNER JOIN schedule_templates st ON b.schedule_template_id = st.id
                    WHERE st.venue_id = ? AND b.booking_at IS NOT NULL AND b.booking_at = b.booking_at_utc
                ', [$venue->id]);

                foreach ($bookings as $booking) {
                    try {
                        $localTime = new DateTime($booking->booking_at, new DateTimeZone($venue->timezone));
                        $utcTime = clone $localTime;
                        $utcTime->setTimezone(new DateTimeZone('UTC'));

                        DB::update('
                            UPDATE bookings
                            SET booking_at_utc = ?
                            WHERE id = ?
                        ', [$utcTime->format('Y-m-d H:i:s'), $booking->id]);
                    } catch (Exception) {
                        // If timezone is invalid, fallback to default method
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_at_utc');
        });
    }
};
