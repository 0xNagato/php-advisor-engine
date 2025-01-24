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
        Schema::create('venue_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('prime_total');
            $table->integer('non_prime_total');
            $table->integer('total_amount');
            $table->string('currency', 3);
            $table->dateTime('due_date');
            $table->string('status');
            $table->string('pdf_path')->nullable();
            $table->json('booking_ids');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_invoices');
    }
};
