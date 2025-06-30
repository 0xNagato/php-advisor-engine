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
        Schema::create('platform_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained();
            $table->foreignId('booking_id')->constrained();

            // Core platform fields
            $table->string('platform_type'); // 'covermanager', 'restoo', etc.
            $table->string('platform_reservation_id')->nullable();
            $table->string('platform_status')->nullable();
            $table->boolean('synced_to_platform')->default(false);
            $table->timestamp('last_synced_at')->nullable();

            // All platform-specific data stored as JSON
            $table->json('platform_data')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['venue_id', 'platform_type']);
            $table->index('booking_id');
            $table->unique(['platform_type', 'platform_reservation_id'], 'platform_reservation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_reservations');
    }
};
