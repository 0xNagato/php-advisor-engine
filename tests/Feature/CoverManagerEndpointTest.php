<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Venue;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoverManagerEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected CoverManagerService $coverManagerService;

    protected Venue $testVenue;

    protected string $testRestaurantId = 'test-restaurant-123';

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('services.covermanager.api_key', 'test-api-key');
        Config::set('services.covermanager.base_url', 'https://beta.covermanager.com/api');
        Config::set('services.covermanager.environment', 'beta');

        $this->coverManagerService = new CoverManagerService;

        // Create test venue with CoverManager platform
        $this->testVenue = Venue::factory()->create();
        $this->testVenue->platforms()->create([
            'platform_type' => 'covermanager',
            'is_enabled' => true,
            'configuration' => [
                'restaurant_id' => $this->testRestaurantId,
                'sync_enabled' => true,
            ],
        ]);
    }

    #[Test]
    public function authentication_endpoint()
    {
        // Mock successful auth response
        Http::fake([
            'https://beta.covermanager.com/api/restaurant/list/test-api-key/' => Http::response([
                'resp' => 1,
                'restaurants' => [],
            ], 200),
        ]);

        $result = $this->coverManagerService->checkAuth($this->testVenue);

        $this->assertTrue($result);

        // Verify the correct endpoint was called
        Http::assertSent(function ($request) {
            return $request->url() === 'https://beta.covermanager.com/api/restaurant/list/test-api-key/';
        });
    }

    #[Test]
    public function authentication_endpoint_failure()
    {
        // Mock failed auth response
        Http::fake([
            'https://beta.covermanager.com/api/restaurant/list/test-api-key/' => Http::response([
                'resp' => 0,
                'error' => 'Invalid API key',
            ], 401),
        ]);

        $result = $this->coverManagerService->checkAuth($this->testVenue);

        $this->assertFalse($result);
    }

    #[Test]
    public function get_restaurants_endpoint()
    {
        $mockResponse = [
            'resp' => 1,
            'restaurants' => [
                [
                    'restaurant' => 'restaurant-1',
                    'name' => 'Test Restaurant 1',
                    'city' => 'Madrid',
                ],
                [
                    'restaurant' => 'restaurant-2',
                    'name' => 'Test Restaurant 2',
                    'city' => 'Madrid',
                ],
            ],
            '_http_status' => 200,
            '_http_successful' => true,
        ];

        Http::fake([
            'https://beta.covermanager.com/api/restaurant/list/test-api-key/Madrid' => Http::response([
                'resp' => 1,
                'restaurants' => [
                    [
                        'restaurant' => 'restaurant-1',
                        'name' => 'Test Restaurant 1',
                        'city' => 'Madrid',
                    ],
                    [
                        'restaurant' => 'restaurant-2',
                        'name' => 'Test Restaurant 2',
                        'city' => 'Madrid',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->coverManagerService->getRestaurants('Madrid');

        $this->assertEquals($mockResponse, $result);
        $this->assertCount(2, $result['restaurants']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://beta.covermanager.com/api/restaurant/list/test-api-key/Madrid';
        });
    }

    #[Test]
    public function get_restaurant_data_endpoint()
    {
        $mockResponse = [
            'resp' => 1,
            'restaurant' => [
                'restaurant' => $this->testRestaurantId,
                'name' => 'Test Restaurant',
                'address' => '123 Test Street',
                'phone' => '+34123456789',
                'email' => 'test@restaurant.com',
            ],
            '_http_status' => 200,
            '_http_successful' => true,
        ];

        Http::fake([
            "https://beta.covermanager.com/api/restaurant/get/test-api-key/{$this->testRestaurantId}" => Http::response([
                'resp' => 1,
                'restaurant' => [
                    'restaurant' => $this->testRestaurantId,
                    'name' => 'Test Restaurant',
                    'address' => '123 Test Street',
                    'phone' => '+34123456789',
                    'email' => 'test@restaurant.com',
                ],
            ], 200),
        ]);

        $result = $this->coverManagerService->getRestaurantData($this->testRestaurantId);

        $this->assertEquals($mockResponse, $result);
        $this->assertEquals($this->testRestaurantId, $result['restaurant']['restaurant']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), "restaurant/get/test-api-key/{$this->testRestaurantId}");
        });
    }

    #[Test]
    public function check_availability_endpoint()
    {
        $date = Carbon::parse('2025-06-15');
        $time = '19:00';
        $partySize = 4;

        $mockResponse = [
            'availability' => [
                'people' => [
                    '4' => [
                        '19:00' => ['discount' => false],
                        '19:30' => ['discount' => false],
                    ],
                ],
                'hours' => [
                    '19:00' => ['4' => ['discount' => false]],
                    '19:30' => ['4' => ['discount' => false]],
                ],
            ],
            '_http_status' => 200,
            '_http_successful' => true,
        ];

        Http::fake([
            'https://beta.covermanager.com/api/reserv/availability' => Http::response([
                'availability' => [
                    'people' => [
                        '4' => [
                            '19:00' => ['discount' => false],
                            '19:30' => ['discount' => false],
                        ],
                    ],
                    'hours' => [
                        '19:00' => ['4' => ['discount' => false]],
                        '19:30' => ['4' => ['discount' => false]],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->coverManagerService->checkAvailability($this->testVenue, $date, $time, $partySize);

        $this->assertEquals($mockResponse, $result);
        $this->assertArrayHasKey('availability', $result);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $headers = $request->headers();

            return $request->url() === 'https://beta.covermanager.com/api/reserv/availability' &&
                   $request->method() === 'POST' &&
                   isset($headers['apikey']) &&
                   $headers['apikey'][0] === 'test-api-key' &&
                   $data['restaurant'] === $this->testRestaurantId &&
                   $data['date'] === '2025-06-15' &&
                   $data['number_people'] === '4';
        });
    }

    #[Test]
    public function create_reservation_endpoint()
    {
        // Create a schedule template for the venue
        $scheduleTemplate = $this->testVenue->scheduleTemplates()->create([
            'day_of_week' => 'monday',
            'start_time' => '19:00',
            'end_time' => '22:00',
            'party_size' => 4,
            'is_available' => true,
            'available_tables' => 10,
            'fee' => 5000, // $50.00 in cents
        ]);

        // Create required dependencies
        $concierge = \App\Models\Concierge::factory()->create();
        $partner = \App\Models\Partner::factory()->create();

        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_first_name' => 'John',
            'guest_last_name' => 'Doe',
            'guest_email' => 'john@example.com',
            'guest_phone' => '+34123456789',
            'guest_count' => 4,
            'notes' => 'Birthday celebration',
            'booking_at' => Carbon::parse('2025-06-15 19:00:00'),
            'concierge_id' => $concierge->id,
            'partner_concierge_id' => $partner->id,
            'partner_venue_id' => $partner->id,
        ]);

        $mockResponse = [
            'resp' => 1,
            'id_reserv' => 'SGwzEu',
            'status' => '1',
            '_http_status' => 200,
            '_http_successful' => true,
        ];

        Http::fake([
            'https://beta.covermanager.com/api/reserv/reserv' => Http::response([
                'resp' => 1,
                'id_reserv' => 'SGwzEu',
                'status' => '1',
            ], 200),
        ]);

        $result = $this->coverManagerService->createReservation($this->testVenue, $booking);

        $this->assertEquals($mockResponse, $result);
        $this->assertEquals('SGwzEu', $result['id_reserv']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $headers = $request->headers();

            return $request->url() === 'https://beta.covermanager.com/api/reserv/reserv' &&
                   $request->method() === 'POST' &&
                   isset($headers['apikey']) &&
                   $headers['apikey'][0] === 'test-api-key' &&
                   $data['restaurant'] === $this->testRestaurantId &&
                   $data['first_name'] === 'John' &&
                   $data['last_name'] === 'Doe' &&
                   $data['email'] === 'john@example.com' &&
                   $data['phone'] === '+34123456789' &&
                   $data['people'] === '4' &&
                   $data['commentary'] === 'Birthday celebration';
        });
    }

    #[Test]
    public function cancel_reservation_endpoint()
    {
        $reservationId = 'SGwzEu';

        Http::fake([
            'https://beta.covermanager.com/api/reserv/cancel_client' => Http::response([
                'resp' => 1,
                'message' => 'OK',
            ], 200),
        ]);

        $result = $this->coverManagerService->cancelReservation($this->testVenue, $reservationId);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($reservationId) {
            $data = $request->data();
            $headers = $request->headers();

            return $request->url() === 'https://beta.covermanager.com/api/reserv/cancel_client' &&
                   $request->method() === 'POST' &&
                   isset($headers['apikey']) &&
                   $headers['apikey'][0] === 'test-api-key' &&
                   $data['id_reserv'] === $reservationId;
        });
    }

    #[Test]
    public function error_handling_for_failed_requests()
    {
        // Test 404 error - now expects the new error structure instead of empty array
        Http::fake([
            'https://beta.covermanager.com/api/restaurant/get/test-api-key/invalid-id' => Http::response([
                'resp' => 0,
                'error' => 'Restaurant not found',
            ], 404),
        ]);

        $result = $this->coverManagerService->getRestaurantData('invalid-id');

        // Should return error structure with HTTP status information
        $this->assertArrayHasKey('_http_status', $result);
        $this->assertEquals(404, $result['_http_status']);
        $this->assertArrayHasKey('_http_error', $result);
        $this->assertFalse($result['_http_successful']);

        // Test 500 error
        Http::fake([
            'https://beta.covermanager.com/api/restaurant/list/test-api-key/' => Http::response([
                'resp' => 0,
                'error' => 'Internal server error',
            ], 500),
        ]);

        $result = $this->coverManagerService->checkAuth($this->testVenue);
        $this->assertFalse($result);
    }

    #[Test]
    public function network_exception_handling()
    {
        // Simulate network timeout
        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $result = $this->coverManagerService->getRestaurants('Madrid');

        // Should return error structure with exception information
        $this->assertArrayHasKey('_http_status', $result);
        $this->assertNull($result['_http_status']);
        $this->assertArrayHasKey('_http_error', $result);
        $this->assertEquals('Connection timeout', $result['_http_error']);
        $this->assertFalse($result['_http_successful']);

        $result = $this->coverManagerService->checkAuth($this->testVenue);
        $this->assertFalse($result);
    }

    #[Test]
    public function venue_without_platform_configuration()
    {
        $venueWithoutPlatform = Venue::factory()->create();

        $result = $this->coverManagerService->checkAuth($venueWithoutPlatform);
        $this->assertFalse($result);

        $result = $this->coverManagerService->checkAvailability(
            $venueWithoutPlatform,
            Carbon::now(),
            '19:00',
            4
        );
        $this->assertEquals([], $result);
    }

    #[Test]
    public function venue_with_disabled_platform()
    {
        $this->testVenue->platforms()->update(['is_enabled' => false]);

        $result = $this->coverManagerService->checkAuth($this->testVenue);
        $this->assertFalse($result);
    }

    #[Test]
    public function venue_with_missing_restaurant_id()
    {
        $this->testVenue->platforms()->update([
            'configuration' => ['sync_enabled' => true], // Missing restaurant_id
        ]);

        $result = $this->coverManagerService->checkAuth($this->testVenue);
        $this->assertFalse($result);
    }

    #[Test]
    public function all_endpoints_with_real_data_structure()
    {
        // This test uses more realistic mock data structures
        $this->markTestSkipped('Enable this test when you have real CoverManager API documentation');

        // Mock realistic responses based on actual CoverManager API
        Http::fake([
            '*restaurant/list*' => Http::response([
                'resp' => 1,
                'restaurants' => [
                    [
                        'restaurant' => 'prima-test',
                        'name' => 'PRIMA TEST',
                        'city' => 'Madrid',
                        'address' => 'Calle Mayor 1',
                        'phone' => '+34912345678',
                    ],
                ],
            ], 200),
            '*reserv/availability*' => Http::response([
                'availability' => [
                    'people' => [
                        '2' => [
                            '19:00' => ['discount' => false],
                            '19:30' => ['discount' => false],
                        ],
                    ],
                    'hours' => [
                        '19:00' => ['2' => ['discount' => false]],
                        '19:30' => ['2' => ['discount' => false]],
                    ],
                ],
            ], 200),
            '*reserv/reserv*' => Http::response([
                'resp' => 1,
                'id_reserv' => 'SGwzEu',
                'status' => '1',
            ], 200),
            '*reserv/cancel_client*' => Http::response([
                'resp' => 1,
                'message' => 'OK',
            ], 200),
        ]);

        // Test all endpoints with realistic data
        $restaurants = $this->coverManagerService->getRestaurants('Madrid');
        $this->assertArrayHasKey('restaurants', $restaurants);

        $availability = $this->coverManagerService->checkAvailability(
            $this->testVenue,
            Carbon::parse('2025-06-15'),
            '19:00',
            2
        );
        $this->assertArrayHasKey('availability', $availability);
    }
}
