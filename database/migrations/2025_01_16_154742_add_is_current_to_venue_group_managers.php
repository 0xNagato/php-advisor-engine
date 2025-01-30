<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_group_managers', function (Blueprint $table) {
            $table->boolean('is_current')->default(false)->after('allowed_venue_ids');
        });
    }

    public function down(): void
    {
        Schema::table('venue_group_managers', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });
    }
};
