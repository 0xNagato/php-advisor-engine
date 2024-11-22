<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add columns if they don't exist
        if (! Schema::hasColumns('venue_onboarding_locations', ['use_non_prime_incentive', 'non_prime_per_diem'])) {
            Schema::table('venue_onboarding_locations', function (Blueprint $table) {
                $table->boolean('use_non_prime_incentive')->default(false);
                $table->decimal('non_prime_per_diem', 8, 2)->nullable();
            });
        }

        // Only drop columns if they exist
        if (Schema::hasColumns('venue_onboardings', ['use_non_prime_incentive', 'non_prime_per_diem'])) {
            Schema::table('venue_onboardings', function (Blueprint $table) {
                $table->dropColumn(['use_non_prime_incentive', 'non_prime_per_diem']);
            });
        }
    }

    public function down(): void
    {
        // Only add columns if they don't exist
        if (! Schema::hasColumns('venue_onboardings', ['use_non_prime_incentive', 'non_prime_per_diem'])) {
            Schema::table('venue_onboardings', function (Blueprint $table) {
                $table->boolean('use_non_prime_incentive')->default(false);
                $table->decimal('non_prime_per_diem', 8, 2)->nullable();
            });
        }

        // Only drop columns if they exist
        if (Schema::hasColumns('venue_onboarding_locations', ['use_non_prime_incentive', 'non_prime_per_diem'])) {
            Schema::table('venue_onboarding_locations', function (Blueprint $table) {
                $table->dropColumn(['use_non_prime_incentive', 'non_prime_per_diem']);
            });
        }
    }
};
