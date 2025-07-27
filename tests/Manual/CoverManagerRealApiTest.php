<?php

namespace Tests\Manual;

use App\Actions\Venue\SyncCoverManagerAvailabilityAction;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenuePlatform;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REAL API TESTS - These hit actual CoverManager endpoints
 *
 * ⚠️  WARNING: These tests make real API calls to CoverManager
 * ⚠️  These can be DELETED after manual verification
 * ⚠️  DO NOT include in automated test suites
 *
 * Based on screenshot data:
 * - Restaurant ID: prima-test
 * - Venue: Playa Soleil
 * - Max party size: 2 people
 * - Available times: 13:30, 13:45, 14:00, 14:15, 14:30, 14:45
 * - Date range: Jul 26, 2025 - Aug 1, 2025
 */
class CoverManagerRealApiTest extends TestCase
{
    use RefreshDatabase;

    protected CoverManagerService $coverManagerService;

    protected Venue $venue;

    protected VenuePlatform $venuePlatform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coverManagerService = app(CoverManagerService::class);

        // Create a test venue with CoverManager integration
        $this->venue = Venue::factory()->create([
            'name' => 'Playa Soleil Test Venue',
        ]);

        // Create VenuePlatform configuration using the known restaurant ID
        $this->venuePlatform = VenuePlatform::factory()->create([
            'venue_id' => $this->venue->id,
            'platform_type' => 'covermanager',
            'is_enabled' => true,
            'configuration' => [
                'restaurant_id' => 'prima-test', // From screenshot
                'api_key' => config('services.covermanager.api_key'),
            ],
        ]);

        // Create schedule templates for testing (party size 2 max)
        ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => '13:30:00',
            'party_size' => 2,
            'is_available' => true,
            'prime_time' => false,
        ]);

        ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => '14:00:00',
            'party_size' => 2,
            'is_available' => true,
            'prime_time' => false,
        ]);
    }

    /**
     * Test: Get list of restaurants from CoverManager
     *
     * Purpose: Verify we can connect to CoverManager and get restaurant list
     */
    public function test_get_restaurants_from_covermanager_api(): void
    {
        echo "\n🔍 Testing CoverManager restaurant list API...\n";

        $restaurants = $this->coverManagerService->getRestaurants('madrid');

        echo "📋 Restaurant list response:\n";
        echo json_encode($restaurants, JSON_PRETTY_PRINT)."\n";

        // Basic checks
        $this->assertIsArray($restaurants);

        if (! empty($restaurants)) {
            echo "✅ Successfully retrieved restaurant list\n";
            echo '📊 Found '.count($restaurants)." restaurants\n";
        } else {
            echo "⚠️  Empty restaurant list (this might be expected)\n";
        }
    }

    /**
     * Test: Get restaurant data for our test restaurant
     *
     * Purpose: Verify the prima-test restaurant exists and we can access its data
     */
    public function test_get_restaurant_data_for_prima_test(): void
    {
        echo "\n🏪 Testing restaurant data retrieval for prima-test...\n";

        $restaurantData = $this->coverManagerService->getRestaurantData('prima-test');

        echo "🏨 Restaurant data response:\n";
        echo json_encode($restaurantData, JSON_PRETTY_PRINT)."\n";

        // Basic checks
        $this->assertIsArray($restaurantData);

        if (isset($restaurantData['resp']) && $restaurantData['resp'] === 1) {
            echo "✅ Successfully retrieved restaurant data\n";
        } else {
            echo '⚠️  Restaurant data error: '.($restaurantData['error'] ?? 'Unknown')."\n";
        }
    }

    /**
     * Test: Check availability for known time slots
     *
     * Purpose: Test availability checking for times we know should have availability
     * Based on screenshot: 13:30, 14:00, 14:15, 14:30, 14:45 for 1-2 people
     */
    public function test_check_availability_for_known_time_slots(): void
    {
        echo "\n📅 Testing availability check for known time slots...\n";

        $testDate = Carbon::parse('2025-07-28'); // Monday from screenshot
        $testTimes = ['13:30', '14:00', '14:15', '14:30', '14:45'];
        $testPartySizes = [1, 2]; // Max party size 2 from screenshot

        foreach ($testPartySizes as $partySize) {
            echo "\n👥 Testing party size: $partySize\n";

            foreach ($testTimes as $time) {
                echo "⏰ Checking availability for $time...\n";

                $availability = $this->coverManagerService->checkAvailabilityRaw(
                    'prima-test',
                    $testDate,
                    $time,
                    $partySize
                );

                if (isset($availability['resp']) && $availability['resp'] === 1) {
                    $hasAvailability = isset($availability['availability']['people'][(string) $partySize][$time]);
                    echo $hasAvailability ? '✅ Available' : '❌ Not available';
                    echo " at $time for $partySize people\n";
                } else {
                    echo '⚠️  API Error: '.($availability['error'] ?? 'Unknown')."\n";
                }

                // Show raw response for debugging
                echo "📄 Raw response:\n";
                echo json_encode($availability, JSON_PRETTY_PRINT)."\n\n";
            }
        }

        $this->assertTrue(true); // Test passes if we get here without exceptions
    }

    /**
     * Test: Sync venue availability with CoverManager
     *
     * Purpose: Test the main sync functionality that determines prime/non-prime
     * This is the core feature we're implementing
     */
    public function test_venue_sync_with_covermanager_availability(): void
    {
        echo "\n🔄 Testing venue availability sync with CoverManager...\n";

        $testDate = Carbon::parse('2025-07-28'); // Monday

        echo "🏨 Venue: {$this->venue->name}\n";
        echo "📅 Date: {$testDate->format('Y-m-d (l)')}\n";
        echo "🏪 Restaurant ID: prima-test\n\n";

        // Execute the sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $testDate, 1);

        if ($result['success']) {
            echo "✅ Sync completed successfully\n";
        } else {
            echo "❌ Sync failed: {$result['message']}\n";
        }

        // Check what VenueTimeSlots were created
        $venueTimeSlots = \App\Models\VenueTimeSlot::where('booking_date', $testDate)
            ->whereHas('scheduleTemplate', function ($query) {
                $query->where('venue_id', $this->venue->id);
            })
            ->with('scheduleTemplate')
            ->get();

        echo "\n📊 Created VenueTimeSlots:\n";
        foreach ($venueTimeSlots as $slot) {
            $template = $slot->scheduleTemplate;
            echo "⏰ {$template->start_time} (Party: {$template->party_size}) - ";
            echo $slot->prime_time ? '🔴 PRIME TIME' : '🟢 NON-PRIME';
            echo "\n";
        }

        // Check activity logs (now summary instead of individual slot logs)
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Venue::class)
            ->where('subject_id', $this->venue->id)
            ->where('description', 'CoverManager availability sync completed')
            ->get();

        echo "\n📋 Activity logs created: ".$activities->count()."\n";

        $this->assertTrue($result['success'], 'Sync should complete successfully');
        $this->assertGreaterThan(0, $venueTimeSlots->count(), 'Should create VenueTimeSlots');
    }

    /**
     * Test: Force booking functionality
     *
     * Purpose: Test the force booking endpoint that bypasses availability checks
     * ⚠️  WARNING: This creates actual reservations that may need cleanup
     */
    public function test_force_booking_functionality(): void
    {
        echo "\n🚀 Testing force booking functionality...\n";
        echo "⚠️  WARNING: This creates real reservations in CoverManager\n\n";

        $testDate = Carbon::parse('2025-07-30')->format('Y-m-d');

        $bookingData = [
            'name' => 'Test Force Booking',
            'email' => 'test@prima.com',
            'phone' => '+34600000000',
            'date' => $testDate,
            'hour' => '20:00', // Time that might not have availability
            'size' => 2,
            'notes' => 'Test force booking from API test - can be cancelled',
        ];

        echo "📋 Booking data:\n";
        echo json_encode($bookingData, JSON_PRETTY_PRINT)."\n\n";

        $response = $this->coverManagerService->createReservationForceRaw(
            'prima-test',
            $bookingData
        );

        echo "📄 Force booking response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT)."\n";

        if ($response && isset($response['resp']) && $response['resp'] === 1) {
            echo "✅ Force booking created successfully\n";

            // If we got a reservation ID, try to cancel it for cleanup
            if (isset($response['id_reserv'])) {
                echo "🧹 Attempting to cancel test reservation for cleanup...\n";

                $cancelResult = $this->coverManagerService->cancelReservationRaw(
                    'prima-test',
                    $response['id_reserv']
                );

                if ($cancelResult) {
                    echo "✅ Test reservation cancelled successfully\n";
                } else {
                    echo "⚠️  Could not cancel test reservation: {$response['id_reserv']}\n";
                    echo "Please manually cancel this reservation in CoverManager\n";
                }
            }
        } else {
            echo "❌ Force booking failed\n";
            echo 'Error: '.($response['error'] ?? 'Unknown')."\n";
        }

        $this->assertIsArray($response, 'Should get a response array');
    }

    /**
     * Test: Calendar availability for date range
     *
     * Purpose: Test the calendar endpoint to see availability across multiple days
     */
    public function test_calendar_availability_check(): void
    {
        echo "\n📅 Testing calendar availability check...\n";

        $startDate = Carbon::parse('2025-07-26');
        $endDate = Carbon::parse('2025-08-01');

        echo "📊 Checking calendar from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n\n";

        $calendar = $this->coverManagerService->checkAvailabilityCalendarRaw(
            'prima-test',
            $startDate,
            $endDate,
            'all',
            '1'
        );

        echo "📄 Calendar response:\n";
        echo json_encode($calendar, JSON_PRETTY_PRINT)."\n";

        if (isset($calendar['resp']) && $calendar['resp'] === 1) {
            echo "✅ Calendar data retrieved successfully\n";

            if (isset($calendar['calendar'])) {
                echo "📊 Calendar summary:\n";
                foreach ($calendar['calendar'] as $date => $dayData) {
                    $hasAvailability = ! empty($dayData['people']);
                    echo "📅 $date: ".($hasAvailability ? '✅ Available' : '❌ No availability')."\n";
                }
            }
        } else {
            echo '❌ Calendar API error: '.($calendar['error'] ?? 'Unknown')."\n";
        }

        $this->assertIsArray($calendar, 'Should get calendar response');
    }

    /**
     * Manual test runner - outputs results to console
     *
     * Run with: php artisan test tests/Manual/CoverManagerRealApiTest.php --verbose
     */
    public function test_run_all_manual_tests(): void
    {
        echo "\n".str_repeat('=', 80)."\n";
        echo "🧪 COVERMANAGER REAL API TEST SUITE\n";
        echo "⚠️  These tests hit real CoverManager endpoints\n";
        echo str_repeat('=', 80)."\n";

        // Run all the tests in sequence
        $this->test_get_restaurants_from_covermanager_api();
        $this->test_get_restaurant_data_for_prima_test();
        $this->test_check_availability_for_known_time_slots();
        $this->test_venue_sync_with_covermanager_availability();
        $this->test_calendar_availability_check();

        // Skip force booking in the full suite to avoid creating unwanted reservations
        echo "\n⚠️  Skipping force booking test in full suite (run individually if needed)\n";

        echo "\n".str_repeat('=', 80)."\n";
        echo "✅ MANUAL API TESTS COMPLETED\n";
        echo "🗑️  These tests can be deleted after verification\n";
        echo str_repeat('=', 80)."\n";
    }
}
