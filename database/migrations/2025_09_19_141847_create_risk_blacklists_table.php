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
        Schema::create('risk_blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // 'domain', 'phone', 'ip', 'name'
            $table->string('value', 255);
            $table->text('reason')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index('type');
            $table->index('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_blacklists');
    }
};
