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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations');
            $table->foreignId('concierge_user_id')->constrained('concierge_profiles');
            $table->foreignId('guest_user_id')->constrained('users');
            $table->string('guest_name');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone');
            $table->integer('guest_count');
            $table->integer('total_fee');
            $table->string('currency')->default('USD');
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
