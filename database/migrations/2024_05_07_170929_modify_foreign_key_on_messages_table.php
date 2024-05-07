<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['announcement_id']);

            // Add the foreign key back with the cascade on delete option
            $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['announcement_id']);

            // Add the foreign key back without the cascade on delete option
            $table->foreign('announcement_id')->references('id')->on('announcements');
        });
    }
};
