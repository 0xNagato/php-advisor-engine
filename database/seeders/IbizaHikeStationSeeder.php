<?php

namespace Database\Seeders;

use App\Enums\VenueStatus;
use App\Enums\VenueType;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue; // Import Carbon
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Import Hash facade
use Illuminate\Support\Facades\Hash;          // Import Str facade
use Illuminate\Support\Str; // Import DB Facade for transaction

class IbizaHikeStationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Ibiza Hike Station...');
        $venueName = 'Ibiza Hike Station';
        $venueSlug = 'ibiza-'.Str::slug($venueName);
        $managerEmail = 'experience@thehikestation.com';

        // --- CLEANUP Section (Only for non-production) ---
        if (! app()->isProduction()) {
            $this->command->warn('Running development cleanup for potentially broken Ibiza Hike Station data...');
            DB::transaction(function () use ($venueSlug) {
                $existingVenue = Venue::where('slug', $venueSlug)->first();

                if ($existingVenue) {
                    $venueId = $existingVenue->id;
                    $this->command->warn("Found existing venue with slug {$venueSlug} (ID: {$venueId}). Deleting related data...");

                    // 1. Get IDs of templates to be deleted
                    $templateIds = ScheduleTemplate::where('venue_id', $venueId)->pluck('id');

                    if ($templateIds->isNotEmpty()) {
                        // 2. Delete dependent Bookings first
                        $deletedBookings = DB::table('bookings')->whereIn('schedule_template_id', $templateIds)->delete();
                        if ($deletedBookings > 0) {
                            $this->command->warn("- Deleted {$deletedBookings} associated Bookings.");
                        }

                        // 3. Now delete the ScheduleTemplates
                        $deletedTemplates = ScheduleTemplate::whereIn('id', $templateIds)->delete(); // Use whereIn for safety
                        $this->command->warn("- Deleted {$deletedTemplates} associated ScheduleTemplates.");
                    } else {
                        $this->command->info('- No ScheduleTemplates found for this venue.');
                    }

                    // 4. Finally, delete the Venue itself
                    $existingVenue->delete();
                    $this->command->warn("- Deleted Venue record (ID: {$venueId}).");
                } else {
                    $this->command->info('No existing venue found with that slug. Skipping cleanup.');
                }
            });
        } else {
            $this->command->info('Skipping development cleanup in production environment.');
        }
        // --- END CLEANUP ---

        // 1. Create/Find Venue Manager User
        // ** IMPORTANT: Replace placeholders with actual user details **
        $managerUser = User::query()->firstOrCreate(['email' => $managerEmail], [
            'first_name' => 'Anastasiya',
            'last_name' => '',
            'password' => Hash::make(Str::random(16)), // Generate secure password hash
            'region' => 'ibiza', // Ensure correct region
            'timezone' => 'Europe/Madrid', // Ensure correct timezone
            'phone' => '+34606946346', // Manager mobile
            'notification_regions' => json_encode(['ibiza']), // Notify for Ibiza region
        ]);

        // Ensure the user has the correct role (adjust role name if needed)
        if (! $managerUser->hasRole('venue_manager')) {
            $managerUser->assignRole('venue_manager');
            $this->command->info("Assigned 'venue_manager' role to {$managerEmail}.");
        }

        // 2. Create Venue Record using create() since cleanup ensures it doesn't exist
        // ** IMPORTANT: Replace placeholders with actual venue details **
        $contactName = 'Anastasiya';
        $contactPhone = '+34606946346';

        // ** Define actual party sizes (5-20) for default template generation **
        $hikePartySizes = array_combine(range(5, 20), range(5, 20)); // Generates [5 => 5, 6 => 6, ..., 20 => 20]

        // Use create() instead of updateOrCreate() after the cleanup step
        $hikeVenue = Venue::query()->create([
            'slug' => $venueSlug, // Set the slug directly
            'name' => $venueName,
            'user_id' => $managerUser->id,
            'venue_type' => VenueType::HIKE_STATION,
            'region' => 'ibiza',
            'timezone' => 'Europe/Madrid',
            'status' => VenueStatus::ACTIVE,
            'contact_phone' => $contactPhone,
            'primary_contact_name' => $contactName,
            'contacts' => [[ // Pass raw array
                'contact_name' => $contactName, 'contact_phone' => $contactPhone,
                'preferences' => ['sms' => true, 'mail' => false, 'database' => false, 'whatsapp' => false],
                'use_for_reservations' => true,
            ]],
            'open_days' => json_encode(['monday' => 'open', 'tuesday' => 'open', 'wednesday' => 'open', 'thursday' => 'open', 'friday' => 'open', 'saturday' => 'open', 'sunday' => 'open']), // Ensure all days listed
            'party_sizes' => json_encode($hikePartySizes),
            'cuisines' => json_encode(['hiking', 'outdoors']),
            'payout_venue' => 0, 'booking_fee' => 0, 'increment_fee' => 0,
            'non_prime_fee_per_head' => 6, 'non_prime_type' => 'paid',
            'is_suspended' => false, 'no_wait' => false, 'is_omakase' => false,
            'logo_path' => null, 'non_prime_time' => null, 'business_hours' => null,
            'minimum_spend' => null, 'cutoff_time' => null, 'daily_prime_bookings_cap' => null,
            'daily_non_prime_bookings_cap' => null, 'omakase_details' => null,
            'omakase_concierge_fee' => null, 'venue_group_id' => null, 'neighborhood' => null,
        ]);
        $this->command->info("Created new Venue: {$venueName} (ID: {$hikeVenue->id})");

        // 3. Modify the Auto-Generated Schedule Templates
        $this->command->info("Modifying Schedule Templates for {$venueName} (ID: {$hikeVenue->id})...");

        // --- Reset ALL templates for this venue first ---
        $resetCount = ScheduleTemplate::query()->where('venue_id', $hikeVenue->id)
            ->update([
                'is_available' => false,
                'available_tables' => 0,
                'prime_time' => false,
                'price_per_head' => 0,
                'minimum_spend_per_guest' => null,
            ]);
        $this->command->info("Reset {$resetCount} default templates.");

        // --- Now, specifically enable and configure the two required slots FOR PARTY SIZES 5-20 ---
        $availableSlotsData = [
            '10:00:00' => ['end_time' => '14:00:00'], // Morning Slot
            '14:00:00' => ['end_time' => '18:00:00'], // Afternoon Slot
        ];
        // Store price per head in currency units (not cents) as the earnings calculation service expects.
        $basePricePerHead = 6; // €6 per hiker
        $partySizesToEnable = range(5, 20);
        $totalAffectedRows = 0;

        foreach ($availableSlotsData as $startTime => $slotConfig) {
            $affectedRows = ScheduleTemplate::query()->where('venue_id', $hikeVenue->id)
                ->where('start_time', $startTime)
                ->whereIn('party_size', $partySizesToEnable)
                ->update([
                    'end_time' => $slotConfig['end_time'],
                    'is_available' => true,
                    'available_tables' => 5,
                ]);

            if ($affectedRows > 0) {
                $totalAffectedRows += $affectedRows;
                $this->command->info("Updated {$affectedRows} templates for start time {$startTime} (Party Sizes 5-20).");
            } else {
                $this->command->warn("Could not find templates to update for start time {$startTime} and party sizes 5-20. Check default schedule creation logic.");
            }
        }

        if ($totalAffectedRows > 0) {
            $this->command->info("Successfully configured {$totalAffectedRows} template slots (10 AM & 2 PM for sizes 5-20) as available.");
        } else {
            $this->command->error("Failed to configure the specific 10 AM / 2 PM slots for party sizes 5-20. Please check the Venue's default schedule templates.");
        }

        $this->command->info('Ibiza Hike Station seeding completed.');
    }
}
