<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->boolean('use_non_prime_incentive')->default(false);
            $table->decimal('non_prime_per_diem', 8, 2)->nullable();
        });

        Schema::table('venue_onboardings', function (Blueprint $table) {
            $table->dropColumn(['use_non_prime_incentive', 'non_prime_per_diem']);
        });
    }

    public function down(): void
    {
        Schema::table('venue_onboardings', function (Blueprint $table) {
            $table->boolean('use_non_prime_incentive')->default(false);
            $table->decimal('non_prime_per_diem', 8, 2)->nullable();
        });

        Schema::table('venue_onboarding_locations', function (Blueprint $table) {
            $table->dropColumn(['use_non_prime_incentive', 'non_prime_per_diem']);
        });
    }
};
