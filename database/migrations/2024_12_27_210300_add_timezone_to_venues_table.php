<?php

use App\Models\Region;
use App\Models\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('timezone')->after('region')->nullable();
        });

        // Update existing venues with their region's timezone
        $venues = Venue::all();
        foreach ($venues as $venue) {
            $region = Region::query()->find($venue->region);
            if ($region) {
                $venue->update(['timezone' => $region->timezone]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
