<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Partner;
use App\Models\PlatformReservation;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenuePlatform;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PlatformReservationForceBookingTest extends TestCase
{
    use RefreshDatabase;

    protected Venue $venue;

    protected VenuePlatform $venuePlatform;

    protected ScheduleTemplate $scheduleTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        // Create partners first (needed for BookingFactory)
        Partner::factory()->count(3)->create();

        // Create a venue with CoverManager integration
        $this->venue = Venue::factory()->create();

        // Create venue platform configuration
        $this->venuePlatform = VenuePlatform::factory()->create([
            'venue_id' => $this->venue->id,
            'platform_type' => 'covermanager',
            'is_enabled' => true,
            'configuration' => ['restaurant_id' => 'test-restaurant-123'],
        ]);

        // Create a schedule template
        $this->scheduleTemplate = ScheduleTemplate::factory()->create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => '19:00:00',
            'party_size' => 4,
            'is_available' => true,
            'prime_time' => true,
        ]);
    }

    public function test_prime_booking_uses_force_method(): void
    {
        // Create a prime booking
        $booking = Booking::factory()->create([
            'schedule_template_id' => $this->scheduleTemplate->id,
            'guest_count' => 4,
            'is_prime' => true,
            'booking_at' => Carbon::parse('2025-07-28 19:00:00'),
            'partner_concierge_id' => null,
            'partner_venue_id' => null,
        ]);

        // Mock CoverManager service to expect force method call
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('createReservationForceRaw')
            ->once()
            ->with(
                'test-restaurant-123',
                Mockery::on(function ($data) use ($booking) {
                    return $data['name'] === $booking->guest_name &&
                           $data['email'] === $booking->guest_email &&
                           $data['phone'] === $booking->guest_phone &&
                           $data['date'] === $booking->booking_at->format('Y-m-d') &&
                           $data['hour'] === $booking->booking_at->format('H:i') &&
                           $data['size'] === $booking->guest_count;
                })
            )
            ->andReturn([
                'id_reserv' => 'force-reservation-123',
                'status' => 'confirmed',
            ]);

        // Should NOT call regular createReservationRaw for prime bookings
        $mockCoverManagerService->shouldNotReceive('createReservationRaw');

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        // Create platform reservation from booking
        $platformReservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        // Assert platform reservation was created successfully
        $this->assertNotNull($platformReservation);
        $this->assertEquals('test-restaurant-123', $this->venuePlatform->getConfig('restaurant_id'));
        $this->assertEquals('force-reservation-123', $platformReservation->platform_reservation_id);
        $this->assertTrue($platformReservation->synced_to_platform);
    }

    public function test_non_prime_booking_uses_regular_method(): void
    {
        // Create a non-prime booking
        $booking = Booking::factory()->create([
            'schedule_template_id' => $this->scheduleTemplate->id,
            'guest_count' => 4,
            'is_prime' => false,
            'booking_at' => Carbon::parse('2025-07-28 19:00:00'),
            'partner_concierge_id' => null,
            'partner_venue_id' => null,
        ]);

        // Mock CoverManager service to expect regular method call
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('createReservationRaw')
            ->once()
            ->with(
                'test-restaurant-123',
                Mockery::on(function ($data) use ($booking) {
                    return $data['name'] === $booking->guest_name &&
                           $data['email'] === $booking->guest_email &&
                           $data['phone'] === $booking->guest_phone &&
                           $data['date'] === $booking->booking_at->format('Y-m-d') &&
                           $data['hour'] === $booking->booking_at->format('H:i') &&
                           $data['size'] === $booking->guest_count;
                })
            )
            ->andReturn([
                'id_reserv' => 'regular-reservation-456',
                'status' => 'confirmed',
            ]);

        // Should NOT call force method for non-prime bookings
        $mockCoverManagerService->shouldNotReceive('createReservationForceRaw');

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        // Create platform reservation from booking
        $platformReservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        // Assert platform reservation was created successfully
        $this->assertNotNull($platformReservation);
        $this->assertEquals('regular-reservation-456', $platformReservation->platform_reservation_id);
        $this->assertTrue($platformReservation->synced_to_platform);
    }

    public function test_sync_to_platform_uses_force_for_prime_booking(): void
    {
        // Create a prime booking
        $booking = Booking::factory()->create([
            'schedule_template_id' => $this->scheduleTemplate->id,
            'guest_count' => 4,
            'is_prime' => true,
            'booking_at' => Carbon::parse('2025-07-28 19:00:00'),
            'partner_concierge_id' => null,
            'partner_venue_id' => null,
        ]);

        // Create platform reservation without syncing first
        $platformReservation = PlatformReservation::factory()->create([
            'venue_id' => $this->venue->id,
            'booking_id' => $booking->id,
            'platform_type' => 'covermanager',
            'synced_to_platform' => false,
            'platform_data' => [
                'reservation_date' => $booking->booking_at->format('Y-m-d'),
                'reservation_time' => $booking->booking_at->format('H:i:s'),
                'customer_name' => $booking->guest_name,
                'customer_email' => $booking->guest_email,
                'customer_phone' => $booking->guest_phone,
                'party_size' => $booking->guest_count,
                'notes' => $booking->notes,
            ],
        ]);

        // Mock CoverManager service for sync operation
        $mockCoverManagerService = Mockery::mock(CoverManagerService::class);
        $mockCoverManagerService->shouldReceive('createReservationForceRaw')
            ->once()
            ->andReturn([
                'id_reserv' => 'synced-force-reservation-789',
                'status' => 'confirmed',
            ]);

        $mockCoverManagerService->shouldNotReceive('createReservationRaw');

        $this->app->instance(CoverManagerService::class, $mockCoverManagerService);

        // Call syncToPlatform
        $result = $platformReservation->syncToPlatform();

        // Assert sync was successful
        $this->assertTrue($result);
        $platformReservation->refresh();
        $this->assertEquals('synced-force-reservation-789', $platformReservation->platform_reservation_id);
        $this->assertTrue($platformReservation->synced_to_platform);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
