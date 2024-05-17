<?php

use App\Models\Restaurant;
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
        Schema::table('restaurants', static function (Blueprint $table) {
            $table->string('slug')->after('restaurant_name');
        });

        $restaurants = Restaurant::all();
        foreach ($restaurants as $restaurant) {
            $restaurant->slug = \Illuminate\Support\Str::slug($restaurant->region.' '.$restaurant->restaurant_name);
            $restaurant->save();
        }

        Schema::table('restaurants', static function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', static function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
