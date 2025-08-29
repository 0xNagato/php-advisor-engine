<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if venues table exists and has metadata column
        if (Schema::hasTable('venues') && Schema::hasColumn('venues', 'metadata')) {
            // Update existing metadata to include googlePhotoUrls array
            DB::statement("
                UPDATE venues 
                SET metadata = jsonb_set(
                    COALESCE(metadata, '{}')::jsonb,
                    '{googlePhotoUrls}',
                    '[]'::jsonb,
                    false
                )
                WHERE metadata IS NOT NULL
                  AND metadata::jsonb ?? 'rating'
                  AND NOT metadata::jsonb ?? 'googlePhotoUrls'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if venues table exists
        if (Schema::hasTable('venues') && Schema::hasColumn('venues', 'metadata')) {
            // Remove googlePhotoUrls from metadata
            DB::statement("
                UPDATE venues 
                SET metadata = metadata::jsonb - 'googlePhotoUrls'
                WHERE metadata IS NOT NULL
                  AND metadata::jsonb ?? 'googlePhotoUrls'
            ");
        }
    }
};
