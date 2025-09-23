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
        Schema::create('risk_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('booking_id')->nullable();
            $table->string('event', 50); // 'scored', 'approved', 'rejected', 'whitelisted', 'blacklisted'
            $table->jsonb('payload'); // Full scoring features and reasons (PII masked)
            $table->bigInteger('user_id')->nullable();
            $table->string('ip_hash', 64)->nullable(); // SHA256 hash of IP
            $table->timestamps();

            $table->index('booking_id');
            $table->index('event');
            $table->index('created_at');
            $table->index(['booking_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_audit_logs');
    }
};
