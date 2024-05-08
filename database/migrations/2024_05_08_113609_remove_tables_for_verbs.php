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
        Schema::dropIfExists('verb_events');
        Schema::dropIfExists('verb_snapshots');
        Schema::dropIfExists('verb_state_events');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
