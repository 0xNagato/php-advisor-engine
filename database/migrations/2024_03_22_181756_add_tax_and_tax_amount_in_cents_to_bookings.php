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
            $table->integer('tax_amount_in_cents')->nullable();
            $table->float('tax')->nullable();
            $table->integer('total_with_tax_in_cents')->nullable();
            $table->string('city')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('tax_amount_in_cents');
            $table->dropColumn('tax');
            $table->dropColumn('total_with_tax_in_cents');
            $table->dropColumn('city');
        });
    }
};
