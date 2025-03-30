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
        Schema::create('booking_customer_reminder_logs', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('booking_id'); // References bookings table
            $table->string('guest_phone'); // Phone number of the guest
            $table->timestamp('sent_at'); // When the reminder was sent
            $table->timestamps(); // Created at and updated at

            // Foreign key constraint for booking_id
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->onDelete('cascade'); // Optional: cascading delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_customer_reminder_logs');
    }
};
