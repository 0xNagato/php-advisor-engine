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
        Schema::table('venue_collections', function (Blueprint $table) {
            // Drop the existing constraints
            $table->dropForeign(['concierge_id']);
            $table->dropUnique(['concierge_id']);
            $table->dropUnique(['vip_code_id']);

            // Make concierge_id nullable
            $table->foreignId('concierge_id')->nullable()->change();

            // Re-add foreign key constraint for concierge_id only
            $table->foreign('concierge_id')->references('id')->on('concierges')->onDelete('cascade');

            // Add unique constraints for 1-to-1 relationships
            $table->unique('concierge_id');
            $table->unique('vip_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_collections', function (Blueprint $table) {
            // Drop the constraints
            $table->dropForeign(['concierge_id']);
            $table->dropForeign(['vip_code_id']);
            $table->dropUnique(['concierge_id']);
            $table->dropUnique(['vip_code_id']);

            // Make concierge_id required again
            $table->foreignId('concierge_id')->nullable(false)->change();

            // Re-add original constraints
            $table->foreign('concierge_id')->references('id')->on('concierges')->onDelete('cascade');
            $table->unique('concierge_id');
            $table->unique('vip_code_id');
        });
    }
};
