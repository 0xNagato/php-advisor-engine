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
        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('uuid')->after('id');
            // remove guest_name and add guest_first_name and guest_last_name
            $table->dropColumn('guest_name');
            $table->string('guest_first_name')->after('concierge_id')->nullable();
            $table->string('guest_last_name')->after('guest_first_name')->nullable();

            // make guest_email, guest_phone nullable
            $table->string('guest_email')->nullable()->change();
            $table->string('guest_phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('uuid');
            $table->string('guest_name')->after('concierge_id');
            $table->dropColumn('guest_first_name');
            $table->dropColumn('guest_last_name');

            $table->string('guest_email')->change();
            $table->string('guest_phone')->change();
        });
    }
};
