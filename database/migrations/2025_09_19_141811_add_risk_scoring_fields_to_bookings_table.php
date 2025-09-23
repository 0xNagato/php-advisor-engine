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
        Schema::table('bookings', function (Blueprint $table) {
            $table->smallInteger('risk_score')->nullable()->after('device');
            $table->string('risk_state', 10)->nullable()->after('risk_score'); // null, 'soft', 'hard'
            $table->jsonb('risk_reasons')->default('[]')->after('risk_state');
            $table->timestamp('reviewed_at')->nullable()->after('risk_reasons');
            $table->bigInteger('reviewed_by')->nullable()->after('reviewed_at');
            $table->string('ip_address', 45)->nullable()->after('reviewed_by'); // Supports IPv6
            $table->string('user_agent', 500)->nullable()->after('ip_address');

            // Indexes for efficient filtering
            $table->index('risk_score');
            $table->index('risk_state');
            $table->index('reviewed_at');
            $table->index(['risk_state', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['risk_state', 'created_at']);
            $table->dropIndex('reviewed_at');
            $table->dropIndex('risk_state');
            $table->dropIndex('risk_score');

            $table->dropColumn([
                'risk_score',
                'risk_state',
                'risk_reasons',
                'reviewed_at',
                'reviewed_by',
                'ip_address',
                'user_agent',
            ]);
        });
    }
};
