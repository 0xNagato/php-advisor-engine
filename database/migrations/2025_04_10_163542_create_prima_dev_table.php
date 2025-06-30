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
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->string('event')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->char('batch_uuid', 36)->nullable();
            $table->timestamps();
            $table->index(['causer_type', 'causer_id'], 'causer');
            $table->index(['subject_type', 'subject_id'], 'subject');
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sender_id')->index('announcements_sender_id_foreign');
            $table->string('title');
            $table->text('message');
            $table->string('region')->nullable();
            $table->json('recipient_roles')->nullable();
            $table->json('recipient_user_ids')->nullable();
            $table->string('call_to_action_title')->nullable();
            $table->string('call_to_action_url')->nullable();
            $table->timestamps();
            $table->dateTime('published_at')->nullable();
        });

        Schema::create('authentication_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('authenticatable_type');
            $table->unsignedBigInteger('authenticatable_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at')->nullable();
            $table->boolean('login_successful')->default(false);
            $table->timestamp('logout_at')->nullable();
            $table->boolean('cleared_by_user')->default(false);
            $table->json('location')->nullable();

            $table->index(['authenticatable_type', 'authenticatable_id']);
        });

        Schema::create('booking_customer_reminder_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id')->index('booking_customer_reminder_logs_booking_id_foreign');
            $table->string('guest_phone');
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::create('booking_modification_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id')->index('booking_modification_requests_booking_id_foreign');
            $table->unsignedBigInteger('requested_by_id')->index('booking_modification_requests_requested_by_id_foreign');
            $table->integer('original_guest_count');
            $table->integer('requested_guest_count');
            $table->time('original_time');
            $table->time('requested_time');
            $table->unsignedBigInteger('original_schedule_template_id')->nullable();
            $table->unsignedBigInteger('requested_schedule_template_id')->nullable();
            $table->string('status');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('schedule_template_id')->nullable()->index('bookings_schedule_template_id_foreign');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('concierge_id')->index('bookings_concierg_id_foreign');
            $table->unsignedBigInteger('partner_concierge_id')->nullable()->index('bookings_partner_concierge_id_foreign');
            $table->unsignedBigInteger('partner_venue_id')->nullable()->index('bookings_partner_restaurant_id_foreign');
            $table->string('guest_first_name')->nullable();
            $table->string('guest_last_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->dateTime('booking_at')->default('2024-07-31 21:08:26');
            $table->dateTime('booking_at_utc')->nullable();
            $table->integer('guest_count');
            $table->integer('total_fee');
            $table->string('currency')->default('USD');
            $table->string('status')->default('confirmed');
            $table->boolean('is_prime')->default(false);
            $table->boolean('no_show')->default(false);
            $table->timestamps();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->json('stripe_charge')->nullable();
            $table->integer('venue_earnings')->default(0);
            $table->integer('concierge_earnings')->default(0);
            $table->integer('charity_earnings')->default(0);
            $table->integer('platform_earnings')->default(0);
            $table->unsignedInteger('partner_concierge_fee')->default(0);
            $table->unsignedInteger('partner_venue_fee')->default(0);
            $table->dateTime('clicked_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('concierge_referral_type')->nullable();
            $table->timestamp('venue_confirmed_at')->nullable();
            $table->timestamp('resent_venue_confirmation_at')->nullable();
            $table->integer('tax_amount_in_cents')->nullable();
            $table->double('tax')->nullable();
            $table->integer('total_with_tax_in_cents')->nullable();
            $table->string('city')->nullable();
            $table->string('invoice_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('vip_code_id')->nullable()->index('bookings_vip_code_id_foreign');
            $table->json('refund_data')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->integer('refunded_guest_count')->nullable();
            $table->integer('platform_earnings_refunded')->default(0);
            $table->integer('total_refunded')->default(0);
            $table->integer('original_total')->default(0);
            $table->json('meta')->nullable();
            $table->char('source', 25)->nullable();
            $table->char('device', 25)->nullable();

            $table->index(['confirmed_at', 'created_at']);
            $table->index(['partner_concierge_id']);
            $table->index(['partner_venue_id']);
            $table->index(['confirmed_at', 'booking_at'], 'idx_bookings_confirmed_at_booking_at');
            $table->index(['confirmed_at', 'booking_at', 'id'], 'idx_bookings_confirmed_booking_at_id');
        });

        Schema::create('breezy_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('authenticatable_type');
            $table->unsignedBigInteger('authenticatable_id');
            $table->string('panel_id')->nullable();
            $table->string('guard')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('concierges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('concierge_user_id_foreign');
            $table->string('hotel_name')->index('concierge_hotel_name_index');
            $table->json('allowed_venue_ids')->nullable();
            $table->unsignedBigInteger('venue_group_id')->nullable()->index('concierges_venue_group_id_foreign');
            $table->string('hotel_phone')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'id'], 'idx_concierges_user_id_id');
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('key')->unique();
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });

        Schema::create('earnings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('referral_earnings_user_id_foreign');
            $table->unsignedBigInteger('booking_id')->index('referral_earnings_booking_id_foreign');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('type');
            $table->integer('amount');
            $table->string('currency')->default('USD');
            $table->timestamps();
            $table->timestamp('confirmed_at')->nullable();
            $table->integer('percentage');
            $table->string('percentage_of');

            $table->index(['booking_id', 'user_id', 'type'], 'idx_earnings_booking_id_user_id_type');
            $table->index(['type', 'user_id', 'booking_id', 'amount', 'currency'],
                'idx_earnings_type_user_booking_amount_currency');
        });

        Schema::create('exports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('completed_at')->nullable();
            $table->string('file_disk');
            $table->string('file_name')->nullable();
            $table->string('exporter');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedBigInteger('user_id')->index('exports_user_id_foreign');
            $table->timestamps();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('feedback_user_id_foreign');
            $table->text('message');
            $table->text('exception_message')->nullable();
            $table->text('exception_trace')->nullable();
            $table->timestamps();
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('messages_user_id_foreign');
            $table->unsignedBigInteger('announcement_id')->index('messages_announcement_id_foreign');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('partners_user_id_foreign');
            $table->integer('percentage');
            $table->string('company_name')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'id'], 'idx_partners_user_id_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('payment_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('payment_items_user_id_foreign');
            $table->unsignedBigInteger('payment_id')->index('payment_items_payment_id_foreign');
            $table->string('currency');
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->integer('amount');
            $table->string('currency')->default('USD');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
        });

        Schema::create('pulse_aggregates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('bucket');
            $table->unsignedMediumInteger('period');
            $table->string('type')->index();
            $table->mediumText('key');
            $table->uuid('key_hash')->storedAs('md5("key")::uuid');
            $table->string('aggregate');
            $table->decimal('value', 20);
            $table->unsignedInteger('count')->nullable();

            $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
            $table->index(['period', 'bucket']);
            $table->index(['period', 'type', 'aggregate', 'bucket']);
        });

        Schema::create('pulse_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('timestamp')->index();
            $table->string('type')->index();
            $table->mediumText('key');
            $table->uuid('key_hash')->storedAs('md5("key")::uuid');
            $table->bigInteger('value')->nullable();

            $table->index(['timestamp', 'type', 'key_hash', 'value']);
        });

        Schema::create('pulse_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('timestamp')->index();
            $table->string('type')->index();
            $table->mediumText('key');
            $table->uuid('key_hash')->storedAs('md5("key")::uuid');
            $table->mediumText('value');

            $table->unique(['type', 'key_hash']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->unsignedBigInteger('referrer_id')->index('concierge_referrals_referrer_id_foreign');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable()->index('concierge_referrals_user_id_foreign');
            $table->dateTime('secured_at')->nullable();
            $table->string('type')->default('concierge');
            $table->string('referrer_type')->default('concierge');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('reminded_at')->nullable();
            $table->string('region_id')->nullable();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id')->index('role_has_permissions_role_id_foreign');

            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id')->index('role_profiles_role_id_foreign');
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('venue_id')->index('schedule_templates_restaurant_id_foreign');
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->index();
            $table->integer('available_tables');
            $table->integer('price_per_head')->nullable();
            $table->boolean('prime_time')->default(false);
            $table->integer('prime_time_fee')->default(0);
            $table->integer('party_size')->default(2);
            $table->timestamps();
            $table->integer('minimum_spend_per_guest')->nullable();

            $table->index(['id', 'venue_id']);
            $table->index(['venue_id', 'day_of_week', 'start_time']);
        });

        Schema::create('scheduled_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('message');
            $table->dateTime('scheduled_at');
            $table->dateTime('scheduled_at_utc')->index();
            $table->enum('status',
                ['scheduled', 'processing', 'sent', 'cancelled', 'failed'])->default('scheduled')->index();
            $table->json('recipient_data');
            $table->json('regions')->nullable();
            $table->unsignedBigInteger('created_by')->index('scheduled_sms_created_by_foreign');
            $table->unsignedInteger('total_recipients');
            $table->dateTime('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('short_url_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('short_url_id')->index('short_url_visits_short_url_id_foreign');
            $table->string('ip_address')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('operating_system_version')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('referer_url')->nullable();
            $table->string('device_type')->nullable();
            $table->timestamp('visited_at');
            $table->timestamps();
        });

        Schema::create('short_urls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('destination_url');
            $table->string('url_key')->unique();
            $table->string('default_short_url');
            $table->boolean('single_use');
            $table->boolean('forward_query_params')->default(false);
            $table->boolean('track_visits');
            $table->integer('redirect_status_code')->default(301);
            $table->boolean('track_ip_address')->default(false);
            $table->boolean('track_operating_system')->default(false);
            $table->boolean('track_operating_system_version')->default(false);
            $table->boolean('track_browser')->default(false);
            $table->boolean('track_browser_version')->default(false);
            $table->boolean('track_referer_url')->default(false);
            $table->boolean('track_device_type')->default(false);
            $table->timestamp('activated_at')->nullable()->default('2024-08-01 01:08:26');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone_number');
            $table->text('message');
            $table->json('response');
            $table->timestamps();
        });

        Schema::create('sms_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('special_pricing_venues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('venue_id')->index('special_pricing_restaurants_restaurant_id_foreign');
            $table->date('date');
            $table->integer('fee');
            $table->timestamps();
        });

        Schema::create('special_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->unsignedBigInteger('venue_id')->index('special_requests_restaurant_id_foreign');
            $table->unsignedBigInteger('concierge_id')->index('special_requests_concierge_id_foreign');
            $table->date('booking_date');
            $table->time('booking_time');
            $table->integer('party_size');
            $table->text('special_request')->nullable();
            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->integer('commission_requested_percentage')->default(10);
            $table->integer('minimum_spend')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unsignedBigInteger('schedule_template_id')->nullable()->index('special_requests_schedule_template_id_foreign');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->text('venue_message')->nullable();
            $table->json('conversations')->nullable();
            $table->json('meta')->nullable();
        });

        Schema::create('telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('sequence');
            $table->char('uuid', 36)->unique();
            $table->char('batch_id', 36)->index();
            $table->string('family_hash')->nullable()->index();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->longText('content');
            $table->dateTime('created_at')->nullable()->index();

            $table->index(['type', 'should_display_on_index']);
        });

        Schema::create('telescope_entries_tags', function (Blueprint $table) {
            $table->char('entry_uuid', 36);
            $table->string('tag')->index();

            $table->primary(['entry_uuid', 'tag']);
        });

        Schema::create('telescope_monitoring', function (Blueprint $table) {
            $table->string('tag')->primary();
        });

        Schema::create('user_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('code');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('secured_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('phone')->nullable();
            $table->json('payout')->nullable();
            $table->integer('charity_percentage')->default(5);
            $table->unsignedBigInteger('partner_referral_id')->nullable()->index('users_partner_referral_id_foreign');
            $table->unsignedBigInteger('concierge_referral_id')->nullable()->index('users_concierge_referral_id_foreign');
            $table->string('timezone')->default('America/New_York');
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('expo_push_token')->nullable();
            $table->json('notification_regions')->nullable();
        });

        Schema::create('venue_group_managers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('venue_group_id')->index('venue_group_managers_venue_group_id_foreign');
            $table->unsignedBigInteger('current_venue_id')->nullable()->index('venue_group_managers_current_venue_id_foreign');
            $table->json('allowed_venue_ids')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'venue_group_id']);
        });

        Schema::create('venue_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('primary_manager_id')->nullable()->index('venue_groups_primary_manager_id_foreign');
            $table->timestamps();
        });

        Schema::create('venue_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('venue_id')->index('venue_invoices_venue_id_foreign');
            $table->unsignedBigInteger('venue_group_id')->nullable()->index('venue_invoices_venue_group_id_foreign');
            $table->unsignedBigInteger('created_by')->index('venue_invoices_created_by_foreign');
            $table->string('invoice_number')->nullable()->unique();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('prime_total');
            $table->integer('non_prime_total');
            $table->integer('total_amount');
            $table->string('currency', 3);
            $table->dateTime('due_date');
            $table->string('status');
            $table->string('pdf_path')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_invoice_url')->nullable();
            $table->json('booking_ids');
            $table->json('venues_data')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('venue_onboarding_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('venue_onboarding_id')->index('venue_onboarding_locations_venue_onboarding_id_foreign');
            $table->unsignedBigInteger('venue_group_id')->nullable()->index('venue_onboarding_locations_venue_group_id_foreign');
            $table->string('name');
            $table->string('region')->default('miami');
            $table->json('prime_hours')->nullable();
            $table->json('booking_hours')->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedBigInteger('created_venue_id')->nullable()->index('venue_onboarding_locations_created_venue_id_foreign');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('use_non_prime_incentive')->default(false);
            $table->decimal('non_prime_per_diem')->nullable();
        });

        Schema::create('venue_onboardings', function (Blueprint $table) {
            $table->bigIncrements('id');
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
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('processed_by_id')->nullable()->index('venue_onboardings_processed_by_id_foreign');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('partner_id')->nullable()->index('venue_onboardings_partner_id_foreign');
            $table->unsignedBigInteger('venue_group_id')->nullable()->index('venue_onboardings_venue_group_id_foreign');
        });

        Schema::create('venue_time_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('schedule_template_id')->index('restaurant_time_slots_schedule_template_id_foreign');
            $table->date('booking_date');
            $table->boolean('prime_time');
            $table->integer('prime_time_fee')->nullable();
            $table->timestamps();
            $table->boolean('is_available')->default(true);
            $table->integer('available_tables')->default(0);
            $table->integer('price_per_head')->nullable();
            $table->integer('minimum_spend_per_guest')->default(0);

            $table->index(['booking_date', 'schedule_template_id']);
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_venues_user_id');
            $table->string('name');
            $table->string('slug')->unique('restaurants_slug_unique');
            $table->string('contact_phone');
            $table->timestamps();
            $table->integer('payout_venue')->default(60);
            $table->string('primary_contact_name')->nullable();
            $table->integer('booking_fee')->default(20000);
            $table->integer('increment_fee')->default(50);
            $table->integer('non_prime_fee_per_head')->default(10);
            $table->enum('non_prime_type', ['free', 'paid'])->default('paid');
            $table->json('open_days')->nullable();
            $table->json('contacts')->nullable();
            $table->boolean('is_suspended')->default(false);
            $table->json('non_prime_time')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('party_sizes')->nullable();
            $table->integer('minimum_spend')->nullable();
            $table->string('region')->default('miami');
            $table->string('timezone')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status')->default('draft');
            $table->string('venue_type')->default('standard')->index();
            $table->boolean('no_wait')->default(false)->comment('Indicates if guests will be seated immediately without waiting');
            $table->time('cutoff_time')->nullable()->comment('Time after which same-day reservations cannot be made');
            $table->integer('daily_prime_bookings_cap')->nullable()->comment('Maximum number of bookings allowed per day (null for no limit)');
            $table->integer('daily_non_prime_bookings_cap')->nullable()->comment('Maximum number of non-prime bookings allowed per day (null for no limit)');
            $table->unsignedBigInteger('venue_group_id')->nullable()->index('venues_venue_group_id_foreign');
            $table->boolean('is_omakase')->default(false);
            $table->text('omakase_details')->nullable();
            $table->integer('omakase_concierge_fee')->nullable();
            $table->json('cuisines')->nullable();
            $table->string('neighborhood')->nullable();
            $table->integer('advance_booking_window')->default(0);
            $table->json('specialty')->nullable();

            $table->index(['user_id', 'region', 'id', 'name'], 'idx_venues_user_region_id_name');
            $table->index(['user_id'], 'restaurant_user_id_foreign');
        });

        Schema::create('vip_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->unsignedBigInteger('concierge_id')->index('vip_codes_concierge_id_foreign');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vip_codes');

        Schema::dropIfExists('venues');

        Schema::dropIfExists('venue_time_slots');

        Schema::dropIfExists('venue_onboardings');

        Schema::dropIfExists('venue_onboarding_locations');

        Schema::dropIfExists('venue_invoices');

        Schema::dropIfExists('venue_groups');

        Schema::dropIfExists('venue_group_managers');

        Schema::dropIfExists('users');

        Schema::dropIfExists('user_codes');

        Schema::dropIfExists('telescope_monitoring');

        Schema::dropIfExists('telescope_entries_tags');

        Schema::dropIfExists('telescope_entries');

        Schema::dropIfExists('special_requests');

        Schema::dropIfExists('special_pricing_venues');

        Schema::dropIfExists('sms_templates');

        Schema::dropIfExists('sms_responses');

        Schema::dropIfExists('short_urls');

        Schema::dropIfExists('short_url_visits');

        Schema::dropIfExists('sessions');

        Schema::dropIfExists('scheduled_sms');

        Schema::dropIfExists('schedule_templates');

        Schema::dropIfExists('roles');

        Schema::dropIfExists('role_profiles');

        Schema::dropIfExists('role_has_permissions');

        Schema::dropIfExists('referrals');

        Schema::dropIfExists('pulse_values');

        Schema::dropIfExists('pulse_entries');

        Schema::dropIfExists('pulse_aggregates');

        Schema::dropIfExists('personal_access_tokens');

        Schema::dropIfExists('permissions');

        Schema::dropIfExists('payments');

        Schema::dropIfExists('payment_items');

        Schema::dropIfExists('password_reset_tokens');

        Schema::dropIfExists('partners');

        Schema::dropIfExists('notifications');

        Schema::dropIfExists('model_has_roles');

        Schema::dropIfExists('model_has_permissions');

        Schema::dropIfExists('messages');

        Schema::dropIfExists('jobs');

        Schema::dropIfExists('job_batches');

        Schema::dropIfExists('feedback');

        Schema::dropIfExists('failed_jobs');

        Schema::dropIfExists('exports');

        Schema::dropIfExists('earnings');

        Schema::dropIfExists('devices');

        Schema::dropIfExists('concierges');

        Schema::dropIfExists('cache_locks');

        Schema::dropIfExists('cache');

        Schema::dropIfExists('breezy_sessions');

        Schema::dropIfExists('bookings');

        Schema::dropIfExists('booking_modification_requests');

        Schema::dropIfExists('booking_customer_reminder_logs');

        Schema::dropIfExists('authentication_log');

        Schema::dropIfExists('announcements');

        Schema::dropIfExists('activity_log');
    }
};
