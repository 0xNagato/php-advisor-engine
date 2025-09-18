<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Import tier ordering from config/venue-tiers.php into venues.tier / venues.tier_position
        $tiersByRegion = Config::get('venue-tiers.tiers', []);

        foreach ($tiersByRegion as $regionId => $tiers) {
            foreach ([1, 2] as $tierNumber) {
                $key = 'tier_'.$tierNumber;
                $venueIds = $tiers[$key] ?? [];

                foreach (array_values($venueIds) as $index => $venueId) {
                    DB::table('venues')
                        ->where('id', (int) $venueId)
                        ->update([
                            'tier' => $tierNumber,
                            'tier_position' => $index + 1,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Roll back imported positions (keep tier if set manually afterward)
        DB::table('venues')->update([
            'tier_position' => null,
        ]);
    }
};
