<?php

use App\Models\Venue;
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
        // First convert existing values to JSON format
        $venues = Venue::query()->whereNotNull('specialty')->get();
        foreach ($venues as $venue) {
            if (! is_array($venue->specialty)) {
                $venue->specialty = [$venue->specialty];
                $venue->save();
            }
        }

        // Then modify the column type to JSON
        Schema::table('venues', function (Blueprint $table) {
            $table->json('specialty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('specialty')->nullable()->change();
        });
    }
};
