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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->string('title');
            $table->text('message');
            $table->json('recipient_roles')->nullable();
            $table->json('recipient_user_ids')->nullable();
            $table->string('call_to_action_title')->nullable();
            $table->string('call_to_action_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
