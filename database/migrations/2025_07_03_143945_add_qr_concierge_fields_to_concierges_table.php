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
        Schema::table('concierges', function (Blueprint $table) {
            $table->boolean('is_qr_concierge')->default(false)->after('venue_group_id');
            $table->integer('revenue_percentage')->default(50)->after('is_qr_concierge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concierges', function (Blueprint $table) {
            $table->dropColumn(['is_qr_concierge', 'revenue_percentage']);
        });
    }
};
