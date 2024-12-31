<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_onboardings', function (Blueprint $table) {
            $table->foreignId('partner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('venue_onboardings', function (Blueprint $table) {
            $table->dropForeignId('venue_onboardings_partner_id_foreign');
            $table->dropColumn('partner_id');
        });
    }
};
