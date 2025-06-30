<?php

use App\Models\Booking;
use App\Models\PlatformReservation;
use App\Models\Venue;
use App\Services\CoverManagerService;
use Carbon\Carbon;

beforeEach(function () {
    // Create a test venue with CoverManager platform
    $this->venue = Venue::factory()->create();
    $this->venue->platforms()->create([
        'platform_type' => 'covermanager',
        'is_enabled' => true,
        'configuration' => [
            'restaurant_id' => 'test-restaurant',
            'sync_enabled' => true,
        ],
    ]);

    // Create a schedule template for the venue
    $this->scheduleTemplate = $this->venue->scheduleTemplates()->create([
        'day_of_week' => 'monday',
        'start_time' => '19:00',
        'end_time' => '22:00',
        'party_size' => 4,
        'is_available' => true,
        'available_tables' => 10,
    ]);

    // Helper function to create bookings with proper attributes
    $this->createBooking = function (array $attributes = []) {
        return Booking::factory()->create(array_merge([
            'schedule_template_id' => $this->scheduleTemplate->id,
            'booking_at' => now()->addDays(1),
        ], $attributes));
    };

    // Mock CoverManager service
    $this->app->bind(CoverManagerService::class, function () {
        return new class
        {
            public function createReservationRaw(string $restaurantId, array $bookingData): ?array
            {
                return [
                    'resp' => 1,
                    'id_reserv' => 'test-reservation-id',
                    'status' => '1',
                ];
            }

            public function cancelReservationRaw(string $restaurantId, string $reservationId): bool
            {
                return true;
            }
        };
    });
});

describe('PlatformReservation Creation', function () {
    it('creates a normal reservation when no duplicates exist', function () {
        $booking = ($this->createBooking)();

        $reservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        expect($reservation)->not->toBeNull()
            ->and($reservation->booking_id)->toBe($booking->id)
            ->and($reservation->platform_type)->toBe('covermanager')
            ->and($reservation->platform_reservation_id)->toBe('test-reservation-id')
            ->and($reservation->synced_to_platform)->toBeTrue()
            ->and($reservation->platform_data['is_duplicate'] ?? false)->toBeFalse();
    });

    it('handles duplicate reservations when CoverManager returns existing reservation ID', function () {
        // Create first booking and reservation
        $firstBooking = ($this->createBooking)();
        $firstReservation = PlatformReservation::createFromBooking($firstBooking, 'covermanager');

        // Create second identical booking
        $secondBooking = ($this->createBooking)([
            'booking_at' => $firstBooking->booking_at,
            'guest_first_name' => $firstBooking->guest_first_name,
            'guest_last_name' => $firstBooking->guest_last_name,
            'guest_email' => $firstBooking->guest_email,
            'guest_phone' => $firstBooking->guest_phone,
            'guest_count' => $firstBooking->guest_count,
        ]);

        // Mock CoverManager returning the same reservation ID (duplicate detection)
        $this->app->bind(CoverManagerService::class, function () {
            return new class
            {
                public function createReservationRaw(string $restaurantId, array $bookingData): ?array
                {
                    return [
                        'resp' => 1,
                        'id_reserv' => 'test-reservation-id', // Same ID as first booking
                        'status' => '1',
                    ];
                }
            };
        });

        $secondReservation = PlatformReservation::createFromBooking($secondBooking, 'covermanager');

        expect($secondReservation)->not->toBeNull()
            ->and($secondReservation->booking_id)->toBe($secondBooking->id)
            ->and($secondReservation->platform_type)->toBe('covermanager')
            ->and($secondReservation->platform_reservation_id)->toBe('test-reservation-id_dup_'.$secondBooking->id)
            ->and($secondReservation->platform_data['is_duplicate'])->toBeTrue()
            ->and($secondReservation->platform_data['original_platform_reservation_id'])->toBe('test-reservation-id')
            ->and($secondReservation->platform_data['linked_to_booking_id'])->toBe($firstBooking->id);
    });

    it('creates duplicate reservation with proper metadata when CoverManager returns existing ID', function () {
        // Create first booking and reservation
        $firstBooking = ($this->createBooking)();
        $firstReservation = PlatformReservation::createFromBooking($firstBooking, 'covermanager');

        // Create second booking that will be detected as duplicate
        $secondBooking = ($this->createBooking)();
        $secondReservation = PlatformReservation::createFromBooking($secondBooking, 'covermanager');

        // Verify both reservations exist
        expect($firstReservation)->not->toBeNull()
            ->and($secondReservation)->not->toBeNull()
            ->and($firstReservation->platform_data['is_duplicate'] ?? false)->toBeFalse()
            ->and($secondReservation->platform_data['is_duplicate'])->toBeTrue()
            ->and($secondReservation->platform_data['linked_to_booking_id'])->toBe($firstBooking->id)
            ->and($secondReservation->platform_data['original_platform_reservation_id'])->toBe('test-reservation-id');
    });
});

describe('PlatformReservation Cancellation', function () {
    it('cancels a normal reservation in CoverManager', function () {
        $booking = ($this->createBooking)();
        $reservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        $result = $reservation->cancelInPlatform();

        expect($result)->toBeTrue()
            ->and($reservation->fresh()->platform_status)->toBe('cancelled');
    });

    it('cancels duplicate reservation locally only when original is still active', function () {
        // Create original reservation
        $originalBooking = ($this->createBooking)();
        $originalReservation = PlatformReservation::createFromBooking($originalBooking, 'covermanager');

        // Create duplicate reservation
        $duplicateBooking = ($this->createBooking)();
        $duplicateReservation = PlatformReservation::create([
            'venue_id' => $this->venue->id,
            'booking_id' => $duplicateBooking->id,
            'platform_type' => 'covermanager',
            'platform_reservation_id' => 'test-reservation-id_dup_'.$duplicateBooking->id,
            'platform_status' => '1',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => [
                'is_duplicate' => true,
                'original_platform_reservation_id' => 'test-reservation-id',
                'linked_to_booking_id' => $originalBooking->id,
                'customer_name' => $duplicateBooking->guest_name,
            ],
        ]);

        // Mock CoverManager service to track if cancelReservationRaw is called
        $cancelCalled = false;
        $this->app->bind(CoverManagerService::class, function () use (&$cancelCalled) {
            return new class($cancelCalled)
            {
                public function __construct(private &$cancelCalled) {}

                public function cancelReservationRaw(string $restaurantId, string $reservationId): bool
                {
                    $this->cancelCalled = true;

                    return true;
                }
            };
        });

        $result = $duplicateReservation->cancelInPlatform();

        expect($result)->toBeTrue()
            ->and($duplicateReservation->fresh()->platform_status)->toBe('cancelled')
            ->and($originalReservation->fresh()->platform_status)->toBe('1') // Original still active
            ->and($cancelCalled)->toBeFalse(); // CoverManager API should not be called
    });

    it('cancels in CoverManager when cancelling the last active reservation', function () {
        // Create original reservation
        $originalBooking = ($this->createBooking)();
        $originalReservation = PlatformReservation::createFromBooking($originalBooking, 'covermanager');

        // Create and cancel a duplicate reservation first
        $duplicateBooking = ($this->createBooking)();
        $duplicateReservation = PlatformReservation::create([
            'venue_id' => $this->venue->id,
            'booking_id' => $duplicateBooking->id,
            'platform_type' => 'covermanager',
            'platform_reservation_id' => 'test-reservation-id_dup_'.$duplicateBooking->id,
            'platform_status' => 'cancelled', // Already cancelled
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => [
                'is_duplicate' => true,
                'original_platform_reservation_id' => 'test-reservation-id',
                'linked_to_booking_id' => $originalBooking->id,
                'customer_name' => $duplicateBooking->guest_name,
            ],
        ]);

        // Mock CoverManager service to track if cancelReservationRaw is called
        $cancelCalled = false;
        $this->app->bind(CoverManagerService::class, function () use (&$cancelCalled) {
            return new class($cancelCalled)
            {
                public function __construct(private &$cancelCalled) {}

                public function cancelReservationRaw(string $restaurantId, string $reservationId): bool
                {
                    $this->cancelCalled = true;

                    return true;
                }
            };
        });

        // Now cancel the original reservation
        $result = $originalReservation->cancelInPlatform();

        expect($result)->toBeTrue()
            ->and($originalReservation->fresh()->platform_status)->toBe('cancelled')
            ->and($cancelCalled)->toBeTrue(); // CoverManager API should be called
    });

    it('auto-cancels duplicate reservations when original is cancelled', function () {
        // Create original reservation
        $originalBooking = ($this->createBooking)();
        $originalReservation = PlatformReservation::createFromBooking($originalBooking, 'covermanager');

        // Create multiple duplicate reservations
        $duplicateBooking1 = ($this->createBooking)();
        $duplicateReservation1 = PlatformReservation::create([
            'venue_id' => $this->venue->id,
            'booking_id' => $duplicateBooking1->id,
            'platform_type' => 'covermanager',
            'platform_reservation_id' => 'test-reservation-id_dup_'.$duplicateBooking1->id,
            'platform_status' => '1',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => [
                'is_duplicate' => true,
                'original_platform_reservation_id' => 'test-reservation-id',
                'linked_to_booking_id' => $originalBooking->id,
                'customer_name' => $duplicateBooking1->guest_name,
            ],
        ]);

        $duplicateBooking2 = ($this->createBooking)();
        $duplicateReservation2 = PlatformReservation::create([
            'venue_id' => $this->venue->id,
            'booking_id' => $duplicateBooking2->id,
            'platform_type' => 'covermanager',
            'platform_reservation_id' => 'test-reservation-id_dup_'.$duplicateBooking2->id,
            'platform_status' => '1',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => [
                'is_duplicate' => true,
                'original_platform_reservation_id' => 'test-reservation-id',
                'linked_to_booking_id' => $originalBooking->id,
                'customer_name' => $duplicateBooking2->guest_name,
            ],
        ]);

        // Cancel the original reservation
        $result = $originalReservation->cancelInPlatform();

        expect($result)->toBeTrue()
            ->and($originalReservation->fresh()->platform_status)->toBe('cancelled')
            ->and($duplicateReservation1->fresh()->platform_status)->toBe('cancelled')
            ->and($duplicateReservation2->fresh()->platform_status)->toBe('cancelled');
    });

    it('uses correct reservation ID for CoverManager API calls', function () {
        $booking = ($this->createBooking)();
        $reservation = PlatformReservation::create([
            'venue_id' => $this->venue->id,
            'booking_id' => $booking->id,
            'platform_type' => 'covermanager',
            'platform_reservation_id' => 'test-reservation-id_dup_123',
            'platform_status' => '1',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => [
                'is_duplicate' => true,
                'original_platform_reservation_id' => 'original-reservation-id',
                'linked_to_booking_id' => 999,
                'customer_name' => $booking->guest_name,
            ],
        ]);

        // Mock CoverManager service to verify correct reservation ID is used
        $usedReservationId = null;
        $this->app->bind(CoverManagerService::class, function () use (&$usedReservationId) {
            return new class($usedReservationId)
            {
                public function __construct(private &$usedReservationId) {}

                public function cancelReservationRaw(string $restaurantId, string $reservationId): bool
                {
                    $this->usedReservationId = $reservationId;

                    return true;
                }
            };
        });

        $reservation->cancelInPlatform();

        expect($usedReservationId)->toBe('original-reservation-id'); // Should use original, not duplicate ID
    });
});

describe('PlatformReservation Edge Cases', function () {
    it('handles missing platform data gracefully', function () {
        $booking = ($this->createBooking)();
        $reservation = PlatformReservation::create([
            'venue_id' => $this->venue->id,
            'booking_id' => $booking->id,
            'platform_type' => 'covermanager',
            'platform_reservation_id' => 'test-reservation-id',
            'platform_status' => '1',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => null, // No platform data
        ]);

        $result = $reservation->cancelInPlatform();

        expect($result)->toBeTrue()
            ->and($reservation->fresh()->platform_status)->toBe('cancelled');
    });

    it('handles venue without CoverManager platform', function () {
        $venueWithoutPlatform = Venue::factory()->create();
        $scheduleTemplate = $venueWithoutPlatform->scheduleTemplates()->create([
            'day_of_week' => 'monday',
            'start_time' => '19:00',
            'end_time' => '22:00',
            'party_size' => 4,
            'is_available' => true,
            'available_tables' => 10,
        ]);
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'booking_at' => now()->addDays(1),
        ]);

        $reservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        expect($reservation)->toBeNull();
    });

    it('handles CoverManager API returning null response', function () {
        $this->app->bind(CoverManagerService::class, function () {
            return new class
            {
                public function createReservationRaw(string $restaurantId, array $bookingData): ?array
                {
                    return null; // API failure
                }
            };
        });

        $booking = ($this->createBooking)();
        $reservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        expect($reservation)->toBeNull();
    });

    it('handles CoverManager API returning error response', function () {
        $this->app->bind(CoverManagerService::class, function () {
            return new class
            {
                public function createReservationRaw(string $restaurantId, array $bookingData): ?array
                {
                    return [
                        'resp' => 0,
                        'error' => 'Hour Not Available',
                    ];
                }
            };
        });

        $booking = ($this->createBooking)();
        $reservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        expect($reservation)->toBeNull();
    });
});
