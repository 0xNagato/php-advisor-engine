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
        Schema::create('venue_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_collection_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->text('note')->nullable(); // Influencer review, hotel recommendation, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['venue_collection_id', 'is_active']);
            $table->index(['venue_id']);

            // Ensure unique venue per collection
            $table->unique(['venue_collection_id', 'venue_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_collection_items');
    }
};
