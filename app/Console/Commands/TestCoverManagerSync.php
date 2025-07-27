<?php

namespace App\Console\Commands;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenuePlatform;
use App\Models\VenueTimeSlot;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Spatie\Activitylog\Models\Activity;

class TestCoverManagerSync extends Command
{
    protected $signature = 'test:covermanager-sync {--create-venue} {--test-api} {--test-sync} {--test-force}';

    protected $description = 'Test CoverManager API integration and sync functionality';

    public function handle(): int
    {
        $this->info('🧪 Testing CoverManager Integration');
        $this->info('Restaurant ID: prima-test');
        $this->info('Max party size: 2');
        $this->newLine();

        if ($this->option('create-venue')) {
            $this->createTestVenue();
        }

        if ($this->option('test-api')) {
            $this->testCoverManagerAPI();
        }

        if ($this->option('test-sync')) {
            $this->testVenueSync();
        }

        if ($this->option('test-force')) {
            $this->testForceBooking();
        }

        if (! $this->hasAnyOption()) {
            $this->info('Available options:');
            $this->info('  --create-venue    Create test venue with CoverManager integration');
            $this->info('  --test-api       Test CoverManager API endpoints');
            $this->info('  --test-sync      Test venue availability sync');
            $this->info('  --test-force     Test force booking functionality');
        }

        return Command::SUCCESS;
    }

    private function hasAnyOption(): bool
    {
        return $this->option('create-venue') ||
               $this->option('test-api') ||
               $this->option('test-sync') ||
               $this->option('test-force');
    }

    private function createTestVenue(): void
    {
        $this->info('🏨 Creating test venue...');

        // Create venue
        $venue = Venue::query()->create([
            'user_id' => 1, // Assuming admin user exists
            'name' => 'Test CoverManager Venue',
            'address' => 'Test Address, Madrid',
            'contact_phone' => '+34600000000',
            'region' => 1,
            'status' => 'active',
        ]);

        // Create CoverManager platform integration
        VenuePlatform::query()->create([
            'venue_id' => $venue->id,
            'platform_type' => 'covermanager',
            'is_enabled' => true,
            'configuration' => [
                'restaurant_id' => 'prima-test',
                'api_key' => config('services.covermanager.api_key'),
            ],
        ]);

        // Create schedule templates (party size 2 max)
        $times = ['13:30:00', '14:00:00', '14:30:00', '15:00:00'];

        foreach ($times as $time) {
            ScheduleTemplate::query()->create([
                'venue_id' => $venue->id,
                'day_of_week' => 'monday',
                'start_time' => $time,
                'party_size' => 2,
                'is_available' => true,
                'prime_time' => false,
                'available_tables' => 1,
                'price_per_head' => 50,
                'minimum_spend_per_guest' => 0,
                'prime_time_fee' => 0,
            ]);
        }

        $this->info("✅ Created venue: {$venue->name} (ID: {$venue->id})");
        $this->info('✅ Created CoverManager platform integration');
        $this->info('✅ Created schedule templates for Monday');
    }

    private function testCoverManagerAPI(): void
    {
        $this->info('🔌 Testing CoverManager API...');

        $service = app(CoverManagerService::class);

        // Test 1: Get restaurants
        $this->info('📋 Getting restaurant list...');
        $restaurants = $service->getRestaurants('madrid');
        $this->info('Response: '.json_encode($restaurants, JSON_PRETTY_PRINT));
        $this->newLine();

        // Test 2: Get restaurant data
        $this->info('🏪 Getting prima-test restaurant data...');
        $restaurantData = $service->getRestaurantData('prima-test');
        $this->info('Response: '.json_encode($restaurantData, JSON_PRETTY_PRINT));
        $this->newLine();

        // Test 3: Check availability for known times
        $this->info('📅 Checking availability for Monday July 28, 2025...');
        $testDate = Carbon::parse('2025-07-28');
        $testTimes = ['13:30', '14:00', '14:30'];

        foreach ($testTimes as $time) {
            $this->info("⏰ Checking $time for 2 people...");
            $availability = $service->checkAvailabilityRaw('prima-test', $testDate, $time, 2);

            // Check if response has availability data (not checking resp field as it's not always present for successful calls)
            if (isset($availability['availability']['people']['2'][$time])) {
                $this->info("✅ Available at $time for 2 people");
            } elseif (isset($availability['availability']['people']['2'])) {
                $this->info("❌ Not available at $time (but other times available)");
            } elseif (isset($availability['resp']) && $availability['resp'] === 0) {
                $this->error('API Error: '.($availability['error'] ?? 'Unknown'));
            } else {
                $this->info('❌ No availability for party size 2');
            }

            $this->info('Full response: '.json_encode($availability, JSON_PRETTY_PRINT));
            $this->newLine();
        }
    }

    private function testVenueSync(): void
    {
        $this->info('🔄 Testing venue sync functionality...');

        // Find a venue with CoverManager integration
        $venue = Venue::query()->whereHas('platforms', function (Builder $query) {
            $query->where('platform_type', 'covermanager')
                ->where('is_enabled', true);
        })->first();

        if (! $venue) {
            $this->error('❌ No venue found with CoverManager integration');
            $this->info('💡 Run with --create-venue first');

            return;
        }

        $this->info("🏨 Testing sync for venue: {$venue->name}");

        $testDate = Carbon::parse('2025-07-28'); // Monday
        $this->info("📅 Sync date: {$testDate->format('Y-m-d (l)')}");

        // Show schedule templates before sync
        $templates = $venue->scheduleTemplates()
            ->where('day_of_week', 'monday')
            ->where('is_available', true)
            ->get();

        $this->info('📋 Schedule templates:');
        foreach ($templates as $template) {
            $this->info("  ⏰ {$template->start_time} (Party: {$template->party_size})");
        }

        // Execute sync
        $this->info('🚀 Running sync...');
        $result = $venue->syncCoverManagerAvailability($testDate, 1);

        if ($result) {
            $this->info('✅ Sync completed successfully');
        } else {
            $this->error('❌ Sync failed');

            return;
        }

        // Show results
        $venueTimeSlots = VenueTimeSlot::query()->where('booking_date', $testDate)
            ->whereHas('scheduleTemplate', function (Builder $query) use ($venue) {
                $query->where('venue_id', $venue->id);
            })
            ->with('scheduleTemplate')
            ->get();

        $this->info('📊 Created VenueTimeSlots:');
        foreach ($venueTimeSlots as $slot) {
            $template = $slot->scheduleTemplate;
            $primeStatus = $slot->prime_time ? '🔴 PRIME' : '🟢 NON-PRIME';
            $this->info("  ⏰ {$template->start_time} (Party: {$template->party_size}) - $primeStatus");
        }

        // Show activity logs
        $activities = Activity::query()->where('subject_type', Venue::class)
            ->where('subject_id', $venue->id)
            ->where('description', 'CoverManager availability synced')
            ->latest()
            ->take(5)
            ->get();

        $this->info("📋 Recent activity logs: {$activities->count()}");
        foreach ($activities as $activity) {
            $props = $activity->properties;
            $this->info("  📝 {$activity->created_at}: Set prime={$props['set_prime']} (CM availability={$props['cm_availability']})");
        }
    }

    private function testForceBooking(): void
    {
        $this->info('🚀 Testing force booking functionality...');
        $this->warn('⚠️  This creates a REAL reservation in CoverManager');

        if (! $this->confirm('Continue with force booking test?')) {
            return;
        }

        $service = app(CoverManagerService::class);
        $testDate = Carbon::parse('2025-07-30')->format('Y-m-d');

        $bookingData = [
            'name' => 'Test Force Booking',
            'email' => 'test@prima.com',
            'phone' => '+34600000000',
            'date' => $testDate,
            'hour' => '20:00', // Time that probably doesn't have availability
            'size' => 2,
            'notes' => 'API Test - Please Cancel',
        ];

        $this->info('📋 Booking data:');
        $this->info(json_encode($bookingData, JSON_PRETTY_PRINT));

        $response = $service->createReservationForceRaw('prima-test', $bookingData);

        $this->info('📄 Response:');
        $this->info(json_encode($response, JSON_PRETTY_PRINT));

        if ($response && isset($response['resp']) && $response['resp'] === 1) {
            $this->info('✅ Force booking created successfully');

            if (isset($response['id_reserv'])) {
                $this->info("🎫 Reservation ID: {$response['id_reserv']}");

                if ($this->confirm('Cancel test reservation for cleanup?')) {
                    $cancelResult = $service->cancelReservationRaw('prima-test', $response['id_reserv']);

                    if ($cancelResult) {
                        $this->info('✅ Test reservation cancelled');
                    } else {
                        $this->error('❌ Could not cancel test reservation');
                        $this->warn("Please manually cancel reservation: {$response['id_reserv']}");
                    }
                }
            }
        } else {
            $this->error('❌ Force booking failed');
            $this->error('Error: '.($response['error'] ?? 'Unknown'));
        }
    }
}
