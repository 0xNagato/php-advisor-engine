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
            $table->boolean('uses_covermanager')->default(false);
            $table->string('covermanager_id')->nullable();
            $table->string('covermanager_api_key')->nullable();
            $table->string('covermanager_slug')->nullable();
            $table->boolean('covermanager_sync_enabled')->default(false);
            $table->timestamp('last_covermanager_sync')->nullable();
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
