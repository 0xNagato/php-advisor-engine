<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIndexIfNotExists('earnings', ['type', 'user_id', 'booking_id', 'amount', 'currency'], 'idx_earnings_type_user_booking_amount_currency');
        $this->createIndexIfNotExists('bookings', ['confirmed_at', 'booking_at', 'id'], 'idx_bookings_confirmed_booking_at_id');
        $this->createIndexIfNotExists('venues', ['user_id', 'region', 'id', 'name'], 'idx_venues_user_region_id_name');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('earnings', 'idx_earnings_type_user_booking_amount_currency');
        $this->dropIndexIfExists('bookings', 'idx_bookings_confirmed_booking_at_id');
        $this->dropIndexIfExists('venues', 'idx_venues_user_region_id_name');
    }

    private function createIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        $indexExists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");

        if (empty($indexExists)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexExists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");

        if (! empty($indexExists)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
