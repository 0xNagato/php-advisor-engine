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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->json('contacts')->nullable();
            $table->dropColumn('secondary_contact_name');
            $table->dropColumn('secondary_contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('contacts');
            $table->string('secondary_contact_name')->nullable();
            $table->string('secondary_contact_phone')->nullable();
        });
    }
};
