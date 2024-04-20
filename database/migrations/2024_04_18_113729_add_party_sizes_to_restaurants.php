<?php

use App\Models\Restaurant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @noinspection PackedHashtableOptimizationInspection
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->json('party_sizes')->nullable();
        });

        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $restaurant->party_sizes = [
                '2' => 2,
                '4' => 4,
                '6' => 6,
                '8' => 8,
            ];
            $restaurant->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('party_sizes');
        });
    }
};
