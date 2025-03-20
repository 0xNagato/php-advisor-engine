<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_invoices', function (Blueprint $table) {
            $table->foreignId('venue_group_id')->nullable()->after('venue_id')
                ->constrained('venue_groups')->nullOnDelete();
            $table->json('venues_data')->nullable()->after('booking_ids');
        });
    }

    public function down(): void
    {
        Schema::table('venue_invoices', function (Blueprint $table) {
            $table->dropForeign(['venue_group_id']);
            $table->dropColumn(['venue_group_id', 'venues_data']);
        });
    }
};
