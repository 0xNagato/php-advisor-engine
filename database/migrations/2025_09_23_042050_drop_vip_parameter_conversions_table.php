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
        Schema::dropIfExists('vip_parameter_conversions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('vip_parameter_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vip_code_id')->constrained()->cascadeOnDelete();
            $table->string('parameter_key');
            $table->text('parameter_value');
            $table->foreignId('vip_link_hit_id')->nullable()->constrained('vip_link_hits')->nullOnDelete();
            $table->foreignId('vip_session_id')->nullable()->constrained('vip_sessions')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->integer('earnings_amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['vip_code_id', 'parameter_key']);
            $table->index(['vip_code_id', 'parameter_key', 'parameter_value']);
            $table->index(['vip_code_id', 'converted_at']);
            $table->index('booking_id');
        });
    }
};
