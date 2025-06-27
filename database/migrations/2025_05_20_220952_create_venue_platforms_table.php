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
        Schema::create('venue_platforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('platform_type'); // 'covermanager', 'restoo', etc.
            $table->boolean('is_enabled')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Add unique constraint to ensure a venue can only have one integration per platform type
            $table->unique(['venue_id', 'platform_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_platforms');
    }
};
