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
        Schema::create('earning_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('error_message');
            $table->integer('restaurant_earnings');
            $table->integer('concierge_earnings');
            $table->integer('concierge_referral_level_1_earnings');
            $table->integer('concierge_referral_level_2_earnings');
            $table->integer('restaurant_partner_earnings');
            $table->integer('concierge_partner_earnings');
            $table->integer('platform_earnings');
            $table->integer('total_local');
            $table->integer('total_fee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earning_errors');
    }
};
