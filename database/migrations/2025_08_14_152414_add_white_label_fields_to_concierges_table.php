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
            $table->string('logo_url')->nullable()->after('can_override_duplicate_checks');
            $table->string('main_color')->nullable()->after('logo_url');
            $table->string('secondary_color')->nullable()->after('main_color');
            $table->string('gradient_start')->nullable()->after('secondary_color');
            $table->string('gradient_end')->nullable()->after('gradient_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concierges', function (Blueprint $table) {
            $table->dropColumn([
                'logo_url',
                'main_color',
                'secondary_color',
                'gradient_start',
                'gradient_end',
            ]);
        });
    }
};
