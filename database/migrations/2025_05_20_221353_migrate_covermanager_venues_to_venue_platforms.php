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
        // First, migrate any existing CoverManager venues to the new platform structure
        $coverManagerVenues = DB::table('venues')
            ->where('uses_covermanager', true)
            ->whereNotNull('covermanager_id')
            ->get();

        foreach ($coverManagerVenues as $venue) {
            DB::table('venue_platforms')->insert([
                'venue_id' => $venue->id,
                'platform_type' => 'covermanager',
                'is_enabled' => true,
                'configuration' => json_encode([
                    'restaurant_id' => $venue->covermanager_id,
                    'sync_enabled' => $venue->covermanager_sync_enabled,
                ]),
                'last_synced_at' => $venue->last_covermanager_sync,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Now remove the old columns from venues table
        Schema::table('venues', function (Blueprint $table) {
            // Keep the columns for now to allow for a smooth transition
            // They will be marked for removal in a future migration after full testing
            // $table->dropColumn([
            //    'uses_covermanager',
            //    'covermanager_id',
            //    'covermanager_sync_enabled',
            //    'last_covermanager_sync',
            // ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If needed, we could convert VenuePlatform records back to direct venue fields,
        // but this is a one-way migration for now
    }
};
