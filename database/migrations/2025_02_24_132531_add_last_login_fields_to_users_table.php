<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('secured_at');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
        });

        // Backfill data from authentication_log
        DB::statement("
            UPDATE users u
            INNER JOIN (
                SELECT 
                    a1.authenticatable_id,
                    a1.login_at,
                    a1.ip_address
                FROM authentication_log a1
                LEFT JOIN authentication_log a2 ON 
                    a1.authenticatable_id = a2.authenticatable_id AND
                    a1.login_at < a2.login_at AND
                    a2.login_successful = 1
                WHERE 
                    a1.authenticatable_type = 'App\\\\Models\\\\User'
                    AND a1.login_successful = 1
                    AND a2.authenticatable_id IS NULL
            ) latest_logins ON u.id = latest_logins.authenticatable_id
            SET 
                u.last_login_at = latest_logins.login_at,
                u.last_login_ip = latest_logins.ip_address
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_login_ip']);
        });
    }
};
