<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_templates', function (Blueprint $table) {
            $table->integer('minimum_spend_per_guest')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_templates', function (Blueprint $table) {
            $table->dropColumn('minimum_spend_per_guest');
        });
    }
};
