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
        Schema::table('vip_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('sanctum_token_id')->nullable()->after('token');
            $table->index('sanctum_token_id');

            // Add foreign key constraint to personal_access_tokens table
            $table->foreign('sanctum_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vip_sessions', function (Blueprint $table) {
            $table->dropForeign(['sanctum_token_id']);
            $table->dropIndex(['sanctum_token_id']);
            $table->dropColumn('sanctum_token_id');
        });
    }
};
