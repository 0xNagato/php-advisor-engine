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
        Schema::create('cover_manager_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('covermanager_reservation_id');
            $table->string('covermanager_status');
            $table->datetime('reservation_date');
            $table->string('reservation_time');
            $table->integer('party_size');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->text('covermanager_response')->nullable();
            $table->boolean('synced_to_covermanager')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Add unique constraint with a shorter name
            $table->unique(['venue_id', 'covermanager_reservation_id'], 'cm_venue_reservation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cover_manager_reservations');
    }
};
