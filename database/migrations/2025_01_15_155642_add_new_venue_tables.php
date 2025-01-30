<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('primary_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('venue_group_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->json('allowed_venue_ids')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'venue_group_id']);
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->foreignId('venue_group_id')->nullable()->constrained()->nullOnDelete();
        });

        Role::create(['name' => 'venue_manager']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_group_id');
        });

        Schema::dropIfExists('venue_group_managers');
        Schema::dropIfExists('venue_groups');
    }
};
