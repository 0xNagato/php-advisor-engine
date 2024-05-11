<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if ($this->foreignKeyExists('restaurants', 'restaurants_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('schedule_templates', function (Blueprint $table) {
            if ($this->foreignKeyExists('schedule_templates', 'schedule_templates_restaurant_id_foreign')) {
                $table->dropForeign(['restaurant_id']);
            }
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', static function (Blueprint $table) {
            if ($this->foreignKeyExists('restaurants', 'restaurants_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
        });

        Schema::table('schedule_templates', static function (Blueprint $table) {
            if ($this->foreignKeyExists('schedule_templates', 'schedule_templates_restaurant_id_foreign')) {
                $table->dropForeign(['restaurant_id']);
            }
        });
    }

    private function foreignKeyExists(string $table, string $key): bool
    {
        $query = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', '=', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', '=', $key)
            ->where('TABLE_NAME', '=', $table)
            ->count();

        return $query > 0;
    }
};
