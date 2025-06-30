<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('announcements', function (Blueprint $table) {
            $table->foreign(['sender_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('booking_customer_reminder_logs', function (Blueprint $table) {
            $table->foreign(['booking_id'])->references(['id'])->on('bookings')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('booking_modification_requests', function (Blueprint $table) {
            $table->foreign(['booking_id'])->references(['id'])->on('bookings')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['requested_by_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('bookings', function (Blueprint $table) {
            $table->foreign(['concierge_id'])->references(['id'])->on('concierges')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['partner_concierge_id'])->references(['id'])->on('partners')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['partner_venue_id'],
                'bookings_partner_restaurant_id_foreign')->references(['id'])->on('partners')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['schedule_template_id'])->references(['id'])->on('schedule_templates')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['vip_code_id'])->references(['id'])->on('vip_codes')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('concierges', function (Blueprint $table) {
            $table->foreign(['user_id'],
                'concierge_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['venue_group_id'])->references(['id'])->on('venue_groups')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('devices', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('earnings', function (Blueprint $table) {
            $table->foreign(['booking_id'],
                'referral_earnings_booking_id_foreign')->references(['id'])->on('bookings')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'],
                'referral_earnings_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('exports', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('feedback', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('messages', function (Blueprint $table) {
            $table->foreign(['announcement_id'])->references(['id'])->on('announcements')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('model_has_permissions', function (Blueprint $table) {
            $table->foreign(['permission_id'])->references(['id'])->on('permissions')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('model_has_roles', function (Blueprint $table) {
            $table->foreign(['role_id'])->references(['id'])->on('roles')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('partners', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('payment_items', function (Blueprint $table) {
            $table->foreign(['payment_id'])->references(['id'])->on('payments')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('referrals', function (Blueprint $table) {
            $table->foreign(['referrer_id'],
                'concierge_referrals_referrer_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'],
                'concierge_referrals_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });

        $schema->table('role_has_permissions', function (Blueprint $table) {
            $table->foreign(['permission_id'])->references(['id'])->on('permissions')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['role_id'])->references(['id'])->on('roles')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('role_profiles', function (Blueprint $table) {
            $table->foreign(['role_id'])->references(['id'])->on('roles')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('schedule_templates', function (Blueprint $table) {
            $table->foreign(['venue_id'],
                'schedule_templates_restaurant_id_foreign')->references(['id'])->on('venues')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('scheduled_sms', function (Blueprint $table) {
            $table->foreign(['created_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('short_url_visits', function (Blueprint $table) {
            $table->foreign(['short_url_id'])->references(['id'])->on('short_urls')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('special_pricing_venues', function (Blueprint $table) {
            $table->foreign(['venue_id'],
                'special_pricing_restaurants_restaurant_id_foreign')->references(['id'])->on('venues')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('special_requests', function (Blueprint $table) {
            $table->foreign(['concierge_id'])->references(['id'])->on('concierges')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venue_id'],
                'special_requests_restaurant_id_foreign')->references(['id'])->on('venues')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['schedule_template_id'])->references(['id'])->on('schedule_templates')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('telescope_entries_tags', function (Blueprint $table) {
            $table->foreign(['entry_uuid'])->references(['uuid'])->on('telescope_entries')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('user_codes', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('users', function (Blueprint $table) {
            $table->foreign(['concierge_referral_id'])->references(['id'])->on('concierges')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['partner_referral_id'])->references(['id'])->on('partners')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('venue_group_managers', function (Blueprint $table) {
            $table->foreign(['current_venue_id'])->references(['id'])->on('venues')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venue_group_id'])->references(['id'])->on('venue_groups')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('venue_groups', function (Blueprint $table) {
            $table->foreign(['primary_manager_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });

        $schema->table('venue_invoices', function (Blueprint $table) {
            $table->foreign(['created_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venue_group_id'])->references(['id'])->on('venue_groups')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['venue_id'])->references(['id'])->on('venues')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('venue_onboarding_locations', function (Blueprint $table) {
            $table->foreign(['created_venue_id'])->references(['id'])->on('venues')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['venue_group_id'])->references(['id'])->on('venue_groups')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['venue_onboarding_id'])->references(['id'])->on('venue_onboardings')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('venue_onboardings', function (Blueprint $table) {
            $table->foreign(['partner_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['processed_by_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['venue_group_id'])->references(['id'])->on('venue_groups')->onUpdate('no action')->onDelete('no action');
        });

        $schema->table('venue_time_slots', function (Blueprint $table) {
            $table->foreign(['schedule_template_id'],
                'restaurant_time_slots_schedule_template_id_foreign')->references(['id'])->on('schedule_templates')->onUpdate('no action')->onDelete('cascade');
        });

        $schema->table('venues', function (Blueprint $table) {
            $table->foreign(['user_id'],
                'restaurant_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'],
                'restaurants_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venue_group_id'])->references(['id'])->on('venue_groups')->onUpdate('no action')->onDelete('set null');
        });

        $schema->table('vip_codes', function (Blueprint $table) {
            $table->foreign(['concierge_id'])->references(['id'])->on('concierges')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('vip_codes', function (Blueprint $table) {
            $table->dropForeign('vip_codes_concierge_id_foreign');
        });

        $schema->table('venues', function (Blueprint $table) {
            $table->dropForeign('restaurant_user_id_foreign');
            $table->dropForeign('restaurants_user_id_foreign');
            $table->dropForeign('venues_venue_group_id_foreign');
        });

        $schema->table('venue_time_slots', function (Blueprint $table) {
            $table->dropForeign('restaurant_time_slots_schedule_template_id_foreign');
        });

        $schema->table('venue_onboardings', function (Blueprint $table) {
            $table->dropForeign('venue_onboardings_partner_id_foreign');
            $table->dropForeign('venue_onboardings_processed_by_id_foreign');
            $table->dropForeign('venue_onboardings_venue_group_id_foreign');
        });

        $schema->table('venue_onboarding_locations', function (Blueprint $table) {
            $table->dropForeign('venue_onboarding_locations_created_venue_id_foreign');
            $table->dropForeign('venue_onboarding_locations_venue_group_id_foreign');
            $table->dropForeign('venue_onboarding_locations_venue_onboarding_id_foreign');
        });

        $schema->table('venue_invoices', function (Blueprint $table) {
            $table->dropForeign('venue_invoices_created_by_foreign');
            $table->dropForeign('venue_invoices_venue_group_id_foreign');
            $table->dropForeign('venue_invoices_venue_id_foreign');
        });

        $schema->table('venue_groups', function (Blueprint $table) {
            $table->dropForeign('venue_groups_primary_manager_id_foreign');
        });

        $schema->table('venue_group_managers', function (Blueprint $table) {
            $table->dropForeign('venue_group_managers_current_venue_id_foreign');
            $table->dropForeign('venue_group_managers_user_id_foreign');
            $table->dropForeign('venue_group_managers_venue_group_id_foreign');
        });

        $schema->table('users', function (Blueprint $table) {
            $table->dropForeign('users_concierge_referral_id_foreign');
            $table->dropForeign('users_partner_referral_id_foreign');
        });

        $schema->table('user_codes', function (Blueprint $table) {
            $table->dropForeign('user_codes_user_id_foreign');
        });

        $schema->table('telescope_entries_tags', function (Blueprint $table) {
            $table->dropForeign('telescope_entries_tags_entry_uuid_foreign');
        });

        $schema->table('special_requests', function (Blueprint $table) {
            $table->dropForeign('special_requests_concierge_id_foreign');
            $table->dropForeign('special_requests_restaurant_id_foreign');
            $table->dropForeign('special_requests_schedule_template_id_foreign');
        });

        $schema->table('special_pricing_venues', function (Blueprint $table) {
            $table->dropForeign('special_pricing_restaurants_restaurant_id_foreign');
        });

        $schema->table('short_url_visits', function (Blueprint $table) {
            $table->dropForeign('short_url_visits_short_url_id_foreign');
        });

        $schema->table('scheduled_sms', function (Blueprint $table) {
            $table->dropForeign('scheduled_sms_created_by_foreign');
        });

        $schema->table('schedule_templates', function (Blueprint $table) {
            $table->dropForeign('schedule_templates_restaurant_id_foreign');
        });

        $schema->table('role_profiles', function (Blueprint $table) {
            $table->dropForeign('role_profiles_role_id_foreign');
            $table->dropForeign('role_profiles_user_id_foreign');
        });

        $schema->table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign('role_has_permissions_permission_id_foreign');
            $table->dropForeign('role_has_permissions_role_id_foreign');
        });

        $schema->table('referrals', function (Blueprint $table) {
            $table->dropForeign('concierge_referrals_referrer_id_foreign');
            $table->dropForeign('concierge_referrals_user_id_foreign');
        });

        $schema->table('payment_items', function (Blueprint $table) {
            $table->dropForeign('payment_items_payment_id_foreign');
            $table->dropForeign('payment_items_user_id_foreign');
        });

        $schema->table('partners', function (Blueprint $table) {
            $table->dropForeign('partners_user_id_foreign');
        });

        $schema->table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign('model_has_roles_role_id_foreign');
        });

        $schema->table('model_has_permissions', function (Blueprint $table) {
            $table->dropForeign('model_has_permissions_permission_id_foreign');
        });

        $schema->table('messages', function (Blueprint $table) {
            $table->dropForeign('messages_announcement_id_foreign');
            $table->dropForeign('messages_user_id_foreign');
        });

        $schema->table('feedback', function (Blueprint $table) {
            $table->dropForeign('feedback_user_id_foreign');
        });

        $schema->table('exports', function (Blueprint $table) {
            $table->dropForeign('exports_user_id_foreign');
        });

        $schema->table('earnings', function (Blueprint $table) {
            $table->dropForeign('referral_earnings_booking_id_foreign');
            $table->dropForeign('referral_earnings_user_id_foreign');
        });

        $schema->table('devices', function (Blueprint $table) {
            $table->dropForeign('devices_user_id_foreign');
        });

        $schema->table('concierges', function (Blueprint $table) {
            $table->dropForeign('concierge_user_id_foreign');
            $table->dropForeign('concierges_venue_group_id_foreign');
        });

        $schema->table('bookings', function (Blueprint $table) {
            $table->dropForeign('bookings_concierge_id_foreign');
            $table->dropForeign('bookings_partner_concierge_id_foreign');
            $table->dropForeign('bookings_partner_restaurant_id_foreign');
            $table->dropForeign('bookings_schedule_template_id_foreign');
            $table->dropForeign('bookings_vip_code_id_foreign');
        });

        $schema->table('booking_modification_requests', function (Blueprint $table) {
            $table->dropForeign('booking_modification_requests_booking_id_foreign');
            $table->dropForeign('booking_modification_requests_requested_by_id_foreign');
        });

        $schema->table('booking_customer_reminder_logs', function (Blueprint $table) {
            $table->dropForeign('booking_customer_reminder_logs_booking_id_foreign');
        });

        $schema->table('announcements', function (Blueprint $table) {
            $table->dropForeign('announcements_sender_id_foreign');
        });
    }
};
