<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('bulk_qr_codes', 'qr_codes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('qr_codes', 'bulk_qr_codes');
    }
};
