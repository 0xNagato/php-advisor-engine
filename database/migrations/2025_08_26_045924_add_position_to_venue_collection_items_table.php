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
        Schema::table('venue_collection_items', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('venue_id');
            $table->index(['venue_collection_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_collection_items', function (Blueprint $table) {
            $table->dropIndex(['venue_collection_id', 'position']);
            $table->dropColumn('position');
        });
    }
};
