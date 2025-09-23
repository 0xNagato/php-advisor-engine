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
        Schema::dropIfExists('vip_link_hits');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('vip_link_hits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vip_code_id')->nullable()->constrained('vip_codes')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->timestamp('visited_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer_url')->nullable();
            $table->text('full_url')->nullable();
            $table->timestamps();

            $table->index('vip_code_id');
            $table->index('visited_at');
        });
    }
};
