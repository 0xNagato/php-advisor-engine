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
        Schema::create('bulk_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('url_key')->unique()->comment('The short URL key');
            $table->string('short_url_id')->nullable()->comment('The ID of the short URL from ash-jc-allen/short-url package');
            $table->string('qr_code_path')->nullable()->comment('Path to the stored QR code SVG file');
            $table->string('name')->nullable()->comment('A descriptive name for this QR code');
            $table->text('notes')->nullable()->comment('Additional notes about this QR code');
            $table->foreignId('concierge_id')->nullable()->constrained()->nullOnDelete()->comment('The concierge assigned to this QR code, if any');
            $table->integer('scan_count')->default(0)->comment('Number of times this QR code has been scanned');
            $table->timestamp('last_scanned_at')->nullable()->comment('When this QR code was last scanned');
            $table->timestamp('assigned_at')->nullable()->comment('When this QR code was assigned to a concierge');
            $table->boolean('is_active')->default(true)->comment('Whether this QR code is active');
            $table->json('meta')->nullable()->comment('Additional metadata');
            $table->timestamps();

            $table->index('url_key');
            $table->index('concierge_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_qr_codes');
    }
};
