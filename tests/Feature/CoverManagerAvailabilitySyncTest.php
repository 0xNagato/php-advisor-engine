<?php

namespace Tests\Feature;

use App\Actions\Venue\SyncCoverManagerAvailabilityAction;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenuePlatform;
use App\Models\VenueTimeSlot;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CoverManagerAvailabilitySyncTest extends TestCase
{
    use RefreshDatabase;

    protected Venue $venue;

    protected ScheduleTemplate $scheduleTemplate;

    protected VenuePlatform $venuePlatform;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a venue with CoverManager integration
        $this->venue = Venue::factory()->create();

        // Create venue platform configuration using proper factory
        $this->venuePlatform = VenuePlatform::factory()->create([
            'venue_id' => $this->venue->id,
            'platform_type' => 'covermanager',
            'is_enabled' => true,
            'configuration' => ['restaurant_id' => 'test-restaurant-123'],
        ]);

        // Create a single schedule template for testing (not the full venue setup)
        $this->scheduleTemplate = ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => '19:00:00',
            'party_size' => 4,
            'is_available' => true,
            'prime_time' => false,
            'available_tables' => 1,
            'price_per_head' => 50,
            'minimum_spend_per_guest' => 0,
            'prime_time_fee' => 0,
        ]);
    }

    public function test_sync_creates_prime_venue_time_slot_when_no_cm_availability(): void
    {
        // Mock CoverManager service to return no availability for any call
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([
                'resp' => 1,
                'calendar' => [
                    '2025-07-28' => [
                        'people' => [
                            // No availability for party size 4
                            '2' => [
                                '19:00' => ['discount' => false],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        $date = Carbon::parse('2025-07-28'); // Monday

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync was successful
        $this->assertTrue($result['success']);

        // Assert VenueTimeSlot was created with prime_time = true (no CM availability)
        $venueTimeSlot = VenueTimeSlot::where('schedule_template_id', $this->scheduleTemplate->id)
            ->where('booking_date', $date)
            ->first();

        $this->assertNotNull($venueTimeSlot);
        $this->assertTrue($venueTimeSlot->prime_time);
        $this->assertTrue($venueTimeSlot->is_available);

        // Assert activity log was created (now as summary instead of individual slot logs)
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Venue::class,
            'subject_id' => $this->venue->id,
            'description' => 'CoverManager availability sync completed',
        ]);
    }

    public function test_sync_creates_non_prime_venue_time_slot_when_cm_has_availability(): void
    {
        // Create a template that defaults to PRIME so we can test CM availability making it NON-PRIME
        $primeTemplate = ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => '19:00:00',
            'party_size' => 4,
            'is_available' => true,
            'prime_time' => true, // Template defaults to PRIME
        ]);

        // Mock CoverManager service to return availability for all calls
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([
                'resp' => 1,
                'calendar' => [
                    '2025-07-28' => [
                        'people' => [
                            '2' => ['19:00' => ['discount' => false]],
                            '4' => ['19:00' => ['discount' => false]], // Available for our party size
                            '6' => ['19:00' => ['discount' => false]],
                            '8' => ['19:00' => ['discount' => false]],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        $date = Carbon::parse('2025-07-28'); // Monday

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync was successful
        $this->assertTrue($result['success']);

        // Assert VenueTimeSlot was created with prime_time = false (CM has availability, overriding template's prime=true)
        $venueTimeSlot = VenueTimeSlot::where('schedule_template_id', $primeTemplate->id)
            ->where('booking_date', $date)
            ->first();

        $this->assertNotNull($venueTimeSlot);
        $this->assertFalse($venueTimeSlot->prime_time); // Should be non-prime due to CM availability
        $this->assertTrue($venueTimeSlot->is_available);
    }

    public function test_sync_skips_human_created_venue_time_slots(): void
    {
        $date = Carbon::parse('2025-07-28'); // Monday

        // Create a VenueTimeSlot that was created by a human
        $existingSlot = VenueTimeSlot::factory()->create([
            'schedule_template_id' => $this->scheduleTemplate->id,
            'booking_date' => $date,
            'prime_time' => true,
        ]);

        // Create activity log indicating human interaction
        Activity::create([
            'log_name' => 'default',
            'description' => 'override_update',
            'subject_type' => Venue::class,
            'subject_id' => $this->venue->id,
            'properties' => [
                'venue_time_slot_id' => $existingSlot->id,
                'action' => 'override_update',
            ],
        ]);

        // Mock CoverManager service - it will be called for other templates but not for our human-created slot
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([
                'resp' => 1,
                'calendar' => [
                    '2025-07-28' => [],
                ],
            ]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync was successful but slot wasn't modified
        $this->assertTrue($result['success']);

        // Assert the existing slot wasn't changed
        $existingSlot->refresh();
        $this->assertTrue($existingSlot->prime_time);

        // Assert no new individual slot activity log was created (we now use summary logs)
        $syncActivitiesForSlot = Activity::where('subject_type', Venue::class)
            ->where('subject_id', $this->venue->id)
            ->where('description', 'CoverManager availability synced')
            ->whereJsonContains('properties->venue_time_slot_id', $existingSlot->id)
            ->get();

        $this->assertCount(0, $syncActivitiesForSlot);
    }

    public function test_sync_updates_existing_automated_venue_time_slots(): void
    {
        $date = Carbon::parse('2025-07-28'); // Monday

        // Create a template that defaults to NON-PRIME
        $nonPrimeTemplate = ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => '19:00:00',
            'party_size' => 4,
            'is_available' => true,
            'prime_time' => false, // Template defaults to NON-PRIME
        ]);

        // Create an existing VenueTimeSlot that overrides the template (set to PRIME)
        $existingSlot = VenueTimeSlot::factory()->create([
            'schedule_template_id' => $nonPrimeTemplate->id,
            'booking_date' => $date,
            'prime_time' => true, // Override: currently set to PRIME
        ]);

        // Create activity log indicating automated sync (not human)
        Activity::create([
            'log_name' => 'default',
            'description' => 'CoverManager availability synced',
            'subject_type' => Venue::class,
            'subject_id' => $this->venue->id,
            'properties' => [
                'venue_time_slot_id' => $existingSlot->id,
            ],
        ]);

        // Mock CoverManager service to return availability (should make it non-prime, matching template default)
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([
                'resp' => 1,
                'calendar' => [
                    '2025-07-28' => [
                        'people' => [
                            '2' => ['19:00' => ['discount' => false]],
                            '4' => ['19:00' => ['discount' => false]], // Available for our party size
                            '6' => ['19:00' => ['discount' => false]],
                            '8' => ['19:00' => ['discount' => false]],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync was successful
        $this->assertTrue($result['success']);

        // Assert the existing override slot was DELETED since CM availability now matches template default
        $this->assertDatabaseMissing('venue_time_slots', [
            'id' => $existingSlot->id,
        ]);
    }

    public function test_sync_handles_cm_api_errors_gracefully(): void
    {
        // Mock CoverManager service to return error response
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([
                'resp' => 0,
                'error' => 'API error',
            ]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        $date = Carbon::parse('2025-07-28');

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync failed due to API error
        $this->assertFalse($result['success']);

        // Assert no VenueTimeSlot was created due to API error
        $venueTimeSlot = VenueTimeSlot::where('schedule_template_id', $this->scheduleTemplate->id)
            ->where('booking_date', $date)
            ->first();

        $this->assertNull($venueTimeSlot);
    }

    public function test_sync_handles_empty_api_response_gracefully(): void
    {
        // Mock CoverManager service to return empty response
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        $date = Carbon::parse('2025-07-28');

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync failed due to empty response
        $this->assertFalse($result['success']);

        // Assert no VenueTimeSlot was created due to empty response
        $venueTimeSlot = VenueTimeSlot::where('schedule_template_id', $this->scheduleTemplate->id)
            ->where('booking_date', $date)
            ->first();

        $this->assertNull($venueTimeSlot);
    }

    public function test_sync_does_not_run_for_disabled_venues(): void
    {
        // Disable CoverManager platform for venue
        $this->venuePlatform->update(['is_enabled' => false]);

        $date = Carbon::parse('2025-07-28');

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync returned false (not enabled)
        $this->assertFalse($result['success']);

        // Assert no VenueTimeSlot was created
        $this->assertDatabaseMissing('venue_time_slots', [
            'schedule_template_id' => $this->scheduleTemplate->id,
            'booking_date' => $date,
        ]);
    }

    public function test_sync_does_not_run_for_venues_without_covermanager_platform(): void
    {
        // Delete the CoverManager platform
        $this->venuePlatform->delete();

        $date = Carbon::parse('2025-07-28');

        // Execute sync
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $date, 1);

        // Assert sync returned false (no platform)
        $this->assertFalse($result['success']);

        // Assert no VenueTimeSlot was created
        $this->assertDatabaseMissing('venue_time_slots', [
            'schedule_template_id' => $this->scheduleTemplate->id,
            'booking_date' => $date,
        ]);
    }

    public function test_sync_processes_multiple_days(): void
    {
        // Create templates for different days with different defaults
        $tuesdayTemplate = ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'tuesday',
            'start_time' => '19:00:00',
            'party_size' => 4,
            'is_available' => true,
            'prime_time' => true, // Tuesday template defaults to PRIME
        ]);

        // Mock CoverManager service for both days
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);

        // Return calendar data for both days
        $mockCoverManagerService->shouldReceive('checkAvailabilityCalendar')
            ->zeroOrMoreTimes()
            ->andReturn([
                'resp' => 1,
                'calendar' => [
                    '2025-07-28' => [
                        // Monday - no availability for party size 4 (becomes prime, matches template default)
                        'people' => [
                            '2' => ['19:00' => ['discount' => false]],
                        ],
                    ],
                    '2025-07-29' => [
                        // Tuesday - has availability for party size 4 (becomes non-prime, overrides template prime=true)
                        'people' => [
                            '2' => ['19:00' => ['discount' => false]],
                            '4' => ['19:00' => ['discount' => false]],
                            '6' => ['19:00' => ['discount' => false]],
                            '8' => ['19:00' => ['discount' => false]],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        $startDate = Carbon::parse('2025-07-28'); // Monday

        // Execute sync for 2 days
        $result = SyncCoverManagerAvailabilityAction::make()->handle($this->venue, $startDate, 2);

        // Assert sync was successful
        $this->assertTrue($result['success']);

        // Check Monday slot (should be prime, OVERRIDING template default non-prime, so venue time slot IS created)
        $mondaySlot = VenueTimeSlot::where('schedule_template_id', $this->scheduleTemplate->id)
            ->where('booking_date', $startDate)
            ->first();
        $this->assertNotNull($mondaySlot); // Override needed: CM has no availability (prime) vs template default (non-prime)
        $this->assertTrue($mondaySlot->prime_time);

        // Check Tuesday slot (should be non-prime, overriding template prime=true, so venue time slot IS created)
        $tuesdaySlot = VenueTimeSlot::where('schedule_template_id', $tuesdayTemplate->id)
            ->where('booking_date', $startDate->copy()->addDay())
            ->first();
        $this->assertNotNull($tuesdaySlot);
        $this->assertFalse($tuesdaySlot->prime_time); // Override: non-prime due to CM availability
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
