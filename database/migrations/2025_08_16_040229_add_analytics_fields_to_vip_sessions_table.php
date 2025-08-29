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
        Schema::table('vip_sessions', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('expires_at');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->timestamp('started_at')->nullable()->after('user_agent');
            $table->timestamp('last_activity_at')->nullable()->after('started_at');

            // Add indexes for analytics queries
            $table->index('started_at');
            $table->index('last_activity_at');
            $table->index(['vip_code_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vip_sessions', function (Blueprint $table) {
            $table->dropIndex(['vip_code_id', 'started_at']);
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex(['started_at']);
            $table->dropColumn(['ip_address', 'user_agent', 'started_at', 'last_activity_at']);
        });
    }
};
