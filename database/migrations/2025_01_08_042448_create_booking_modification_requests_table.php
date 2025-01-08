<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // create_booking_modification_requests_table migration
    public function up(): void
    {
        Schema::create('booking_modification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_id')->constrained('users');
            $table->integer('original_guest_count');
            $table->integer('requested_guest_count');
            $table->time('original_time');
            $table->time('requested_time');
            $table->foreignId('original_schedule_template_id')
                ->nullable()
                ->constrained('schedule_templates')
                ->name('bmr_orig_schedule_template_fk');
            $table->foreignId('requested_schedule_template_id')
                ->nullable()
                ->constrained('schedule_templates')
                ->name('bmr_req_schedule_template_fk');
            $table->string('status');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
};
