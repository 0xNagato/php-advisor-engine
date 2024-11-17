<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_onboardings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->integer('venue_count');
            $table->boolean('has_logos')->default(false);
            $table->boolean('agreement_accepted')->default(false);
            $table->timestamp('agreement_accepted_at')->nullable();
            $table->json('prime_hours')->nullable();
            $table->boolean('use_non_prime_incentive')->default(false);
            $table->decimal('non_prime_per_diem', 8, 2)->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('processed_by_id')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('venue_onboarding_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_onboarding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->foreignId('created_venue_id')->nullable()->constrained('venues');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_onboarding_locations');
        Schema::dropIfExists('venue_onboardings');
    }
};
