<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Remove the columns
            $table->dropColumn('time_slot');
            $table->dropColumn('guest_capacity');
            $table->dropColumn('status');

            // Add new columns
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_closed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Add the columns back
            $table->string('time_slot')->nullable();
            $table->integer('guest_capacity')->nullable();
            $table->string('status')->nullable();

            // Remove the new columns
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
            $table->dropColumn('is_closed');
        });
    }
};
