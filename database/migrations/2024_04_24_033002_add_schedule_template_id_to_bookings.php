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
        Schema::table('bookings', function (Blueprint $table) {


            $table->dropForeign('bookings_schedule_id_foreign');
            $table->dropIndex('bookings_schedule_id_foreign');
            $table->dropColumn('schedule_id');
            $table->foreignId('schedule_template_id')->nullable()->after('id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign('bookings_schedule_template_id_foreign');
            $table->dropColumn('schedule_template_id');
            $table->foreignId('schedule_id')->nullable()->after('id')->constrained();
        });
    }
};
