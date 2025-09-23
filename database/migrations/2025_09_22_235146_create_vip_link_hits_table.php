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
        Schema::create('vip_link_hits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vip_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 32)->nullable();
            $table->timestamp('visited_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer_url')->nullable();
            $table->text('full_url')->nullable();
            $table->text('raw_query')->nullable();
            $table->jsonb('query_params')->nullable();
            $table->timestamps();

            $table->index('vip_code_id');
            $table->index('visited_at');
        });

        // Create a GIN index for efficient JSONB querying on PostgreSQL
        try {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('CREATE INDEX vip_link_hits_query_params_gin ON vip_link_hits USING GIN (query_params)');
            }
        } catch (Throwable) {
            // Index creation failure should not block migration in non-PgSQL environments
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the GIN index if it exists (PostgreSQL only)
        try {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS vip_link_hits_query_params_gin');
            }
        } catch (Throwable) {
            // Ignore
        }

        Schema::dropIfExists('vip_link_hits');
    }
};
