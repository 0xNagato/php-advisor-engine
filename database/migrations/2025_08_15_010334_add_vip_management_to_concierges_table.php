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
        Schema::table('concierges', function (Blueprint $table) {
            $table->boolean('can_manage_vip_branding')->default(false)->after('can_manage_own_collections');
            $table->boolean('can_manage_vip_collections')->default(false)->after('can_manage_vip_branding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concierges', function (Blueprint $table) {
            $table->dropColumn(['can_manage_vip_branding', 'can_manage_vip_collections']);
        });
    }
};
