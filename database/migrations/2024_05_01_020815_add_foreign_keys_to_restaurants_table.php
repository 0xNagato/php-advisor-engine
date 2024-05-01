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
        Schema::table('restaurants', function (Blueprint $table) {
            $connection = Schema::getConnection();
            $dbSchemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $dbSchemaManager->listTableDetails($table->getTable());

            if ($doctrineTable->hasForeignKey('restaurants_user_id_foreign')) {
                $table->dropForeign('restaurants_user_id_foreign');
            }
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('schedule_templates', function (Blueprint $table) {
            $connection = Schema::getConnection();
            $dbSchemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $dbSchemaManager->listTableDetails($table->getTable());

            if ($doctrineTable->hasForeignKey('schedule_templates_restaurant_id_foreign')) {
                $table->dropForeign('schedule_templates_restaurant_id_foreign');
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
            $table->dropForeign('restaurants_user_id_foreign');
        });

        Schema::table('schedule_templates', static function (Blueprint $table) {
            $table->dropForeign('schedule_templates_restaurant_id_foreign');
        });
    }
};
