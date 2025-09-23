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
        Schema::table('vip_sessions', function (Blueprint $table): void {
            $table->jsonb('query_params')->nullable()->after('user_agent');
            $table->text('landing_url')->nullable()->after('query_params');
            $table->text('referer_url')->nullable()->after('landing_url');
        });

        try {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('CREATE INDEX vip_sessions_query_params_gin ON vip_sessions USING GIN (query_params)');
            }
        } catch (Throwable) {
            // Ignore if not PostgreSQL or extension not available
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS vip_sessions_query_params_gin');
            }
        } catch (Throwable) {
            // Ignore
        }

        Schema::table('vip_sessions', function (Blueprint $table): void {
            $table->dropColumn(['query_params', 'landing_url', 'referer_url']);
        });
    }
};
