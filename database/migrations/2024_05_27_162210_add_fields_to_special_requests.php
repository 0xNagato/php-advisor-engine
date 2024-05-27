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
        Schema::table('special_requests', static function (Blueprint $table) {
            $table->foreignId('schedule_template_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable();
            $table->text('restaurant_message')->nullable();
            $table->json('conversations')->nullable();
            $table->json('meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_requests', static function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_template_id');
            $table->dropColumn('booking_id');
            $table->dropColumn('restaurant_message');
            $table->dropColumn('conversations');
            $table->dropColumn('meta');
        });
    }
};
