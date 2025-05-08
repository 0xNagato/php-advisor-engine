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
        Schema::create('scheduled_sms', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->dateTime('scheduled_at');
            $table->dateTime('scheduled_at_utc');
            $table->enum('status', ['scheduled', 'processing', 'sent', 'cancelled', 'failed'])->default('scheduled');
            $table->json('recipient_data');
            $table->json('regions')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedInteger('total_recipients');
            $table->dateTime('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at_utc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_sms');
    }
};
