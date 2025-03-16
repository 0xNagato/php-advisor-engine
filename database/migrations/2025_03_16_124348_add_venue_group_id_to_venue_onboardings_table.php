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
        Schema::table('venue_onboardings', function (Blueprint $table) {
            $table->foreignId('venue_group_id')->nullable()->after('partner_id')->constrained('venue_groups');
            $table->text('additional_notes')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_onboardings', function (Blueprint $table) {
            $table->dropForeign(['venue_group_id']);
            $table->dropColumn(['venue_group_id', 'additional_notes']);
        });
    }
};
