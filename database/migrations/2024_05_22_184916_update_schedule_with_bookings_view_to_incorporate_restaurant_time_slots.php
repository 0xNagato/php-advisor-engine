<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS schedule_with_bookings');
        DB::statement("
            CREATE OR REPLACE VIEW schedule_with_bookings AS
            WITH RECURSIVE `date_range` AS (
                SELECT
                    (CURDATE() - INTERVAL 1 DAY) AS `date`
                UNION ALL
                SELECT
                    (`date_range`.`date` + INTERVAL 1 DAY)
                FROM
                    `date_range`
                WHERE
                    (`date_range`.`date` < (CURDATE() + INTERVAL 30 DAY))
            )
            SELECT
                `st`.`id` AS `id`,
                `st`.`id` AS `schedule_template_id`,
                `st`.`restaurant_id` AS `restaurant_id`,
                `st`.`day_of_week` AS `day_of_week`,
                `st`.`start_time` AS `start_time`,
                `st`.`end_time` AS `end_time`,
                `st`.`is_available` AS `is_available`,
                `st`.`available_tables` AS `available_tables`,
                COALESCE(`rts`.`prime_time`, `st`.`prime_time`) AS `prime_time`,
                `st`.`prime_time_fee` AS `prime_time_fee`,
                `st`.`party_size` AS `party_size`,
                `dr`.`date` AS `booking_date`,
                DATE_FORMAT(CAST(CONCAT(DATE_FORMAT(`dr`.`date`, '%Y-%m-%d'), ' ', TIME_FORMAT(`st`.`start_time`, '%H:%i:%s')) AS DATETIME), '%Y-%m-%d %H:%i:%s') AS `booking_at`,
                DATE_FORMAT(CAST(CONCAT(DATE_FORMAT(`dr`.`date`, '%Y-%m-%d'), ' ', TIME_FORMAT(`st`.`start_time`, '%H:%i:%s')) AS DATETIME), '%Y-%m-%d %H:%i:%s') AS `schedule_start`,
                DATE_FORMAT(CAST(CONCAT(DATE_FORMAT(`dr`.`date`, '%Y-%m-%d'), ' ', TIME_FORMAT(`st`.`end_time`, '%H:%i:%s')) AS DATETIME), '%Y-%m-%d %H:%i:%s') AS `schedule_end`,
                (`st`.`available_tables` - IFNULL(`b`.`booked_count`, 0)) AS `remaining_tables`,
                COALESCE(`sp`.`fee`, `r`.`booking_fee`) AS `effective_fee`
            FROM
                `date_range` `dr`
                JOIN `concierge`.`schedule_templates` `st` ON (DAYNAME(`dr`.`date`) = `st`.`day_of_week`)
                LEFT JOIN (
                    SELECT
                        `concierge`.`bookings`.`schedule_template_id` AS `schedule_template_id`,
                        COUNT(0) AS `booked_count`
                    FROM
                        `concierge`.`bookings`
                    WHERE
                        (`concierge`.`bookings`.`status` = 'confirmed')
                    GROUP BY
                        `concierge`.`bookings`.`schedule_template_id`
                ) `b` ON (`st`.`id` = `b`.`schedule_template_id`)
                LEFT JOIN `concierge`.`special_pricing_restaurants` `sp` ON (`sp`.`restaurant_id` = `st`.`restaurant_id` AND `sp`.`date` = `dr`.`date`)
                LEFT JOIN `concierge`.`restaurants` `r` ON (`r`.`id` = `st`.`restaurant_id`)
                LEFT JOIN `concierge`.`restaurant_time_slots` `rts` ON (`rts`.`schedule_template_id` = `st`.`id` AND `rts`.`booking_date` = `dr`.`date`);
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS schedule_with_bookings');
    }
};
