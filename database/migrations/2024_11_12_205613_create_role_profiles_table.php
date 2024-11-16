<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Add a trigger to enforce only one active profile per user
        DB::unprepared('
            CREATE TRIGGER ensure_single_active_profile
            BEFORE INSERT ON role_profiles
            FOR EACH ROW
            BEGIN
                IF NEW.is_active = 1 AND (
                    SELECT COUNT(*) FROM role_profiles
                    WHERE user_id = NEW.user_id AND is_active = 1
                ) > 0 THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "User can only have one active profile";
                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS ensure_single_active_profile');
        Schema::dropIfExists('role_profiles');
    }
};
