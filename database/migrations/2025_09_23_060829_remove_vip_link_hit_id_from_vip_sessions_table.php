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
            // Check if column exists before trying to drop it
            if (Schema::hasColumn('vip_sessions', 'vip_link_hit_id')) {
                // Try to drop foreign key if it exists
                try {
                    $table->dropForeign(['vip_link_hit_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
                $table->dropColumn('vip_link_hit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vip_sessions', function (Blueprint $table) {
            $table->foreignId('vip_link_hit_id')->nullable()
                ->after('vip_code_id')
                ->constrained('vip_link_hits')
                ->nullOnDelete();
        });
    }
};
