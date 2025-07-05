<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added missing import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First drop the foreign key constraint
        Schema::table('scheduled_sms', function (Blueprint $table) {
            $table->dropForeign('scheduled_sms_created_by_foreign');
        });

        // Rename the table
        Schema::rename('scheduled_sms', 'sms_messages');

        // Add the type field and recreate foreign key
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->enum('type', ['scheduled', 'immediate'])->default('scheduled')->after('status');

            // Recreate foreign key with new name
            $table->foreign('created_by', 'sms_messages_created_by_foreign')
                ->references('id')->on('users');
        });

        // Set existing records to 'scheduled' type
        DB::table('sms_messages')->update(['type' => 'scheduled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropForeign('sms_messages_created_by_foreign');
            $table->dropColumn('type');
        });

        // Rename back to original
        Schema::rename('sms_messages', 'scheduled_sms');

        // Recreate original foreign key
        Schema::table('scheduled_sms', function (Blueprint $table) {
            $table->foreign('created_by', 'scheduled_sms_created_by_foreign')
                ->references('id')->on('users');
        });
    }
};
