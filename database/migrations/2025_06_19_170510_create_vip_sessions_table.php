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
        Schema::create('vip_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vip_code_id')->constrained()->onDelete('cascade');
            $table->string('token', 128)->unique(); // Hashed token
            $table->timestamp('expires_at');
            $table->timestamps();

            // Index for performance on token lookups
            $table->index(['token', 'expires_at']);

            // Index for cleaning up expired sessions
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vip_sessions');
    }
};
