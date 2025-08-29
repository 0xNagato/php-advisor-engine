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
        Schema::create('venue_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concierge_id')->constrained()->onDelete('cascade');
            $table->foreignId('vip_code_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['concierge_id', 'is_active']);
            $table->index(['vip_code_id', 'is_active']);

            // Unique constraints for 1-to-1 relationships
            $table->unique('concierge_id');
            $table->unique('vip_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_collections');
    }
};
