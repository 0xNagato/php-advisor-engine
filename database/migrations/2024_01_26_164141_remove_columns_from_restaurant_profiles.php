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
        Schema::table('restaurant_profiles', function (Blueprint $table) {
            $table->dropColumn('sunday_hours_of_operation');
            $table->dropColumn('monday_hours_of_operation');
            $table->dropColumn('tuesday_hours_of_operation');
            $table->dropColumn('wednesday_hours_of_operation');
            $table->dropColumn('thursday_hours_of_operation');
            $table->dropColumn('friday_hours_of_operation');
            $table->dropColumn('saturday_hours_of_operation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_profiles', function (Blueprint $table) {
            $table->jsonb('sunday_hours_of_operation');
            $table->jsonb('monday_hours_of_operation');
            $table->jsonb('tuesday_hours_of_operation');
            $table->jsonb('wednesday_hours_of_operation');
            $table->jsonb('thursday_hours_of_operation');
            $table->jsonb('friday_hours_of_operation');
            $table->jsonb('saturday_hours_of_operation');
        });
    }
};
