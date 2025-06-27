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
        Schema::create('restoo_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('restoo_reservation_id')->nullable();
            $table->string('restoo_status')->nullable();
            $table->datetime('reservation_datetime');
            $table->integer('party_size');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->json('restoo_response')->nullable();
            $table->boolean('synced_to_restoo')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Add unique constraint
            $table->unique(['venue_id', 'restoo_reservation_id'], 'restoo_venue_reservation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restoo_reservations');
    }
};
