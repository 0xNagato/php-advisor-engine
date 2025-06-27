<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (! Schema::hasColumn('venues', 'covermanager_api_key')) {
                $table->string('covermanager_api_key')->nullable();
            }
            if (! Schema::hasColumn('venues', 'covermanager_slug')) {
                $table->string('covermanager_slug')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'uses_covermanager',
                'covermanager_id',
                'covermanager_api_key',
                'covermanager_slug',
                'covermanager_sync_enabled',
                'last_covermanager_sync',
            ]);
        });
    }
};
