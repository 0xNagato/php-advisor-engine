<?php

namespace App\Models;

use App\Services\CoverManagerService;
use App\Services\RestooService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * @mixin IdeHelperPlatformReservation
 */
class PlatformReservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'venue_id',
        'booking_id',
        'platform_type',
        'platform_reservation_id',
        'platform_status',
        'synced_to_platform',
        'last_synced_at',
        'platform_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_to_platform' => 'boolean',
            'last_synced_at' => 'datetime',
            'platform_data' => 'array',
        ];
    }

    /**
     * Get the venue that owns the reservation.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the booking associated with the reservation.
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope to filter by platform type.
     */
    public function scopeForPlatform($query, string $platformType)
    {
        return $query->where('platform_type', $platformType);
    }

    /**
     * Scope for CoverManager reservations.
     */
    public function scopeCoverManager($query)
    {
        return $query->where('platform_type', 'covermanager');
    }

    /**
     * Scope for Restoo reservations.
     */
    public function scopeRestoo($query)
    {
        return $query->where('platform_type', 'restoo');
    }

    /**
     * Create a platform reservation from a booking.
     */
    public static function createFromBooking(Booking $booking, string $platformType): ?self
    {
        return match ($platformType) {
            'covermanager' => self::createCoverManagerReservation($booking),
            'restoo' => self::createRestooReservation($booking),
            default => null,
        };
    }

    /**
     * Create a CoverManager reservation from a booking.
     */
    protected static function createCoverManagerReservation(Booking $booking): ?self
    {
        $venue = $booking->venue;

        if (! $venue->usesCoverManager()) {
            return null;
        }

        $coverManagerService = app(CoverManagerService::class);
        if (! $coverManagerService) {
            return null;
        }

        // Get restaurant ID from venue platform configuration
        $platform = $venue->getPlatform('covermanager');
        if (! $platform || ! $platform->is_enabled) {
            Log::error("CoverManager platform not enabled for venue {$venue->id}");

            return null;
        }

        $restaurantId = $platform->getConfig('restaurant_id');
        if (! $restaurantId) {
            Log::error("Missing CoverManager restaurant ID for venue {$venue->id}");

            return null;
        }

        // Format data for CoverManager API
        $bookingData = [
            'name' => $booking->guest_name,
            'email' => $booking->guest_email,
            'phone' => $booking->guest_phone,
            'date' => $booking->booking_at->format('Y-m-d'),
            'hour' => $booking->booking_at->format('H:i'),
            'size' => $booking->guest_count,
            'notes' => $booking->notes,
        ];

        // Use force booking for prime bookings, regular booking for non-prime
        if ($booking->is_prime) {
            $response = $coverManagerService->createReservationForceRaw($restaurantId, $bookingData);
        } else {
            $response = $coverManagerService->createReservationRaw($restaurantId, $bookingData);
        }

        if (! $response || ! isset($response['id_reserv'])) {
            $errorMessage = 'Unknown error';
            $httpStatus = null;
            $httpError = null;

            if ($response && isset($response['status'])) {
                $errorMessage = $response['status'];
            } elseif ($response && isset($response['error'])) {
                $errorMessage = $response['error'];
            } elseif (! $response) {
                $errorMessage = 'No response from CoverManager API';
            }

            // Extract HTTP status and error details if available
            if ($response) {
                $httpStatus = $response['_http_status'] ?? null;
                $httpError = $response['_http_error'] ?? null;
            }

            Log::error("CoverManager create reservation failed for booking {$booking->id} at {$venue->name}: {$errorMessage}", [
                'booking_id' => $booking->id,
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'restaurant_id' => $restaurantId,
                'booking_data' => $bookingData,
                'api_response' => $response,
                'error_summary' => $errorMessage,
                'http_status' => $httpStatus,
                'http_error' => $httpError,
            ]);

            return null;
        }

        // Check if this reservation ID already exists (CoverManager may return existing ID for duplicates)
        $existingReservation = self::query()->where('platform_type', 'covermanager')
            ->where('platform_reservation_id', $response['id_reserv'])
            ->first();

        if ($existingReservation) {
            Log::warning("CoverManager returned existing reservation ID {$response['id_reserv']} for booking {$booking->id} - this booking appears to be a duplicate of booking {$existingReservation->booking_id}", [
                'new_booking_id' => $booking->id,
                'existing_booking_id' => $existingReservation->booking_id,
                'platform_reservation_id' => $response['id_reserv'],
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
            ]);

            // Still create a record for this booking, but mark it as linked to the existing reservation
            $reservation = self::query()->create([
                'venue_id' => $venue->id,
                'booking_id' => $booking->id,
                'platform_type' => 'covermanager',
                'platform_reservation_id' => $response['id_reserv'].'_dup_'.$booking->id, // Make it unique
                'platform_status' => $response['status'] ?? 'unknown',
                'synced_to_platform' => true,
                'last_synced_at' => Carbon::now(),
                'platform_data' => [
                    'reservation_date' => $booking->booking_at->format('Y-m-d'),
                    'reservation_time' => $booking->booking_at->format('H:i:s'),
                    'customer_name' => $booking->guest_name,
                    'customer_email' => $booking->guest_email,
                    'customer_phone' => $booking->guest_phone,
                    'party_size' => $booking->guest_count,
                    'notes' => $booking->notes,
                    'covermanager_response' => $response,
                    'is_duplicate' => true,
                    'original_platform_reservation_id' => $response['id_reserv'],
                    'linked_to_booking_id' => $existingReservation->booking_id,
                ],
            ]);
        } else {
            // Create the database record after successful API call
            $reservation = self::query()->create([
                'venue_id' => $venue->id,
                'booking_id' => $booking->id,
                'platform_type' => 'covermanager',
                'platform_reservation_id' => $response['id_reserv'],
                'platform_status' => $response['status'] ?? 'unknown',
                'synced_to_platform' => true,
                'last_synced_at' => Carbon::now(),
                'platform_data' => [
                    'reservation_date' => $booking->booking_at->format('Y-m-d'),
                    'reservation_time' => $booking->booking_at->format('H:i:s'),
                    'customer_name' => $booking->guest_name,
                    'customer_email' => $booking->guest_email,
                    'customer_phone' => $booking->guest_phone,
                    'party_size' => $booking->guest_count,
                    'notes' => $booking->notes,
                    'covermanager_response' => $response,
                ],
            ]);
        }

        Log::info("CoverManager create reservation successful for booking {$booking->id} at {$venue->name}", [
            'booking_id' => $booking->id,
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'restaurant_id' => $restaurantId,
            'platform_reservation_id' => $response['id_reserv'],
            'reservation_date' => $booking->booking_at->format('Y-m-d'),
            'reservation_time' => $booking->booking_at->format('H:i'),
            'party_size' => $booking->guest_count,
            'customer_name' => $booking->guest_name,
            'status' => $response['status'] ?? 'unknown',
        ]);

        return $reservation;
    }

    /**
     * Create a Restoo reservation from a booking.
     */
    protected static function createRestooReservation(Booking $booking): ?self
    {
        $venue = $booking->venue;

        if (! $venue->hasPlatform('restoo')) {
            return null;
        }

        $restooService = app(RestooService::class);
        if (! $restooService) {
            return null;
        }

        // Call the Restoo API first
        $response = $restooService->createReservation($venue, $booking);

        if (! $response || ! isset($response['uuid'])) {
            $errorMessage = 'Unknown error';
            $httpStatus = null;
            $httpError = null;

            if ($response && isset($response['error'])) {
                $errorMessage = $response['error'];
            } elseif ($response && isset($response['message'])) {
                $errorMessage = $response['message'];
            } elseif (! $response) {
                $errorMessage = 'No response from Restoo API';
            }

            // Extract HTTP status and error details if available
            if ($response) {
                $httpStatus = $response['_http_status'] ?? null;
                $httpError = $response['_http_error'] ?? null;
            }

            Log::error("Restoo create reservation failed for booking {$booking->id} at {$venue->name}: {$errorMessage}", [
                'booking_id' => $booking->id,
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'api_response' => $response,
                'error_summary' => $errorMessage,
                'http_status' => $httpStatus,
                'http_error' => $httpError,
            ]);

            return null;
        }

        // Create the database record after successful API call
        $reservation = self::query()->create([
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
            'platform_type' => 'restoo',
            'platform_reservation_id' => $response['uuid'] ?? null,
            'platform_status' => $response['status'] ?? 'unknown',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => [
                'reservation_datetime' => $booking->booking_at->toISOString(),
                'customer_name' => $booking->guest_name,
                'customer_email' => $booking->guest_email,
                'customer_phone' => $booking->guest_phone,
                'party_size' => $booking->guest_count,
                'notes' => $booking->notes,
                'restoo_response' => $response,
            ],
        ]);

        Log::info("Restoo create reservation successful for booking {$booking->id} at {$venue->name}", [
            'booking_id' => $booking->id,
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'reservation_id' => $reservation->id,
            'platform_reservation_id' => $response['uuid'] ?? null,
            'reservation_date' => $booking->booking_at->format('Y-m-d'),
            'reservation_time' => $booking->booking_at->format('H:i'),
            'party_size' => $booking->guest_count,
            'customer_name' => $booking->guest_name,
            'status' => $response['status'] ?? 'unknown',
        ]);

        return $reservation;
    }

    /**
     * Sync this reservation to its platform.
     */
    public function syncToPlatform(): bool
    {
        return match ($this->platform_type) {
            'covermanager' => $this->syncToCoverManager(),
            'restoo' => $this->syncToRestoo(),
            default => false,
        };
    }

    /**
     * Cancel this reservation in its platform.
     */
    public function cancelInPlatform(): bool
    {
        return match ($this->platform_type) {
            'covermanager' => $this->cancelInCoverManager(),
            'restoo' => $this->cancelInRestoo(),
            default => false,
        };
    }

    /**
     * Sync this reservation to CoverManager.
     */
    protected function syncToCoverManager(): bool
    {
        // If already synced, return true
        if ($this->synced_to_platform && $this->platform_reservation_id) {
            return true;
        }

        if (! $this->venue->usesCoverManager()) {
            return false;
        }

        $coverManagerService = app(CoverManagerService::class);
        if (! $coverManagerService) {
            return false;
        }

        // Get restaurant ID from venue platform configuration
        $platform = $this->venue->getPlatform('covermanager');
        if (! $platform || ! $platform->is_enabled) {
            Log::error("CoverManager platform not enabled for venue {$this->venue->id}");

            return false;
        }

        $restaurantId = $platform->getConfig('restaurant_id');
        if (! $restaurantId) {
            Log::error("Missing CoverManager restaurant ID for venue {$this->venue->id}");

            return false;
        }

        $platformData = $this->platform_data ?? [];

        // Format data for CoverManager API
        $bookingData = [
            'name' => $platformData['customer_name'] ?? '',
            'email' => $platformData['customer_email'] ?? '',
            'phone' => $platformData['customer_phone'] ?? '',
            'date' => $platformData['reservation_date'] ?? '',
            'hour' => substr($platformData['reservation_time'] ?? '', 0, 5), // H:i format
            'size' => $platformData['party_size'] ?? 1,
            'notes' => $platformData['notes'] ?? '',
        ];

        // Use force booking for prime bookings, regular booking for non-prime
        $booking = $this->booking;
        if ($booking && $booking->is_prime) {
            $response = $coverManagerService->createReservationForceRaw($restaurantId, $bookingData);
        } else {
            $response = $coverManagerService->createReservationRaw($restaurantId, $bookingData);
        }

        if (! $response || ! isset($response['id_reserv'])) {
            $errorMessage = 'Unknown error';
            $httpStatus = null;
            $httpError = null;

            if ($response && isset($response['status'])) {
                $errorMessage = $response['status'];
            } elseif ($response && isset($response['error'])) {
                $errorMessage = $response['error'];
            } elseif (! $response) {
                $errorMessage = 'No response from CoverManager API';
            }

            // Extract HTTP status and error details if available
            if ($response) {
                $httpStatus = $response['_http_status'] ?? null;
                $httpError = $response['_http_error'] ?? null;
            }

            Log::error("CoverManager sync reservation failed for reservation {$this->id} at {$this->venue->name}: {$errorMessage}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'restaurant_id' => $restaurantId,
                'booking_data' => $bookingData,
                'api_response' => $response,
                'error_summary' => $errorMessage,
                'http_status' => $httpStatus,
                'http_error' => $httpError,
            ]);

            return false;
        }

        // Update the reservation record
        $this->update([
            'platform_reservation_id' => $response['id_reserv'],
            'platform_status' => $response['status'] ?? 'unknown',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => array_merge($platformData, [
                'covermanager_response' => $response,
            ]),
        ]);

        Log::info("CoverManager sync reservation successful for reservation {$this->id} at {$this->venue->name}", [
            'reservation_id' => $this->id,
            'venue_id' => $this->venue->id,
            'venue_name' => $this->venue->name,
            'restaurant_id' => $restaurantId,
            'platform_reservation_id' => $response['id_reserv'],
            'customer_name' => $platformData['customer_name'] ?? '',
            'status' => $response['status'] ?? 'unknown',
        ]);

        return true;
    }

    /**
     * Sync this reservation to Restoo.
     */
    protected function syncToRestoo(): bool
    {
        // If already synced, return true
        if ($this->synced_to_platform && $this->platform_reservation_id) {
            return true;
        }

        $venue = $this->venue;

        if (! $venue->hasPlatform('restoo')) {
            Log::error("Restoo sync reservation failed for venue {$venue->name}: Venue does not have Restoo platform", [
                'venue_id' => $venue?->id,
                'reservation_id' => $this->id,
            ]);

            return false;
        }

        $booking = $this->booking;
        if (! $booking) {
            Log::error("Restoo sync reservation failed for reservation {$this->id}: No booking found for reservation", [
                'reservation_id' => $this->id,
                'booking_id' => $this->booking_id,
            ]);

            return false;
        }

        $restooService = app(RestooService::class);
        if (! $restooService) {
            Log::error("Restoo sync reservation failed for reservation {$this->id}: Failed to instantiate RestooService");

            return false;
        }

        // Call the Restoo API
        $response = $restooService->createReservation($venue, $booking);

        if (! $response || ! isset($response['uuid'])) {
            $errorMessage = 'Unknown error';
            $httpStatus = null;
            $httpError = null;

            if ($response && isset($response['error'])) {
                $errorMessage = $response['error'];
            } elseif ($response && isset($response['message'])) {
                $errorMessage = $response['message'];
            } elseif (! $response) {
                $errorMessage = 'No response from Restoo API';
            }

            // Extract HTTP status and error details if available
            if ($response) {
                $httpStatus = $response['_http_status'] ?? null;
                $httpError = $response['_http_error'] ?? null;
            }

            Log::error("Restoo sync reservation failed for reservation {$this->id} at {$this->venue->name}: {$errorMessage}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'booking_id' => $booking->id,
                'api_response' => $response,
                'error_summary' => $errorMessage,
                'http_status' => $httpStatus,
                'http_error' => $httpError,
            ]);

            return false;
        }

        $platformData = $this->platform_data ?? [];

        // Update the reservation record
        $this->update([
            'platform_reservation_id' => $response['uuid'] ?? null,
            'platform_status' => $response['status'] ?? 'unknown',
            'synced_to_platform' => true,
            'last_synced_at' => Carbon::now(),
            'platform_data' => array_merge($platformData, [
                'restoo_response' => $response,
            ]),
        ]);

        Log::info("Restoo sync reservation successful for booking {$booking->id} at {$venue->name}", [
            'reservation_id' => $this->id,
            'venue_id' => $this->venue->id,
            'venue_name' => $this->venue->name,
            'booking_id' => $booking->id,
            'platform_reservation_id' => $response['uuid'] ?? null,
            'customer_name' => $platformData['customer_name'] ?? '',
            'status' => $response['status'] ?? 'unknown',
        ]);

        return true;
    }

    /**
     * Cancel this reservation in CoverManager.
     */
    protected function cancelInCoverManager(): bool
    {
        if (! $this->venue->usesCoverManager()) {
            return false;
        }

        // If not synced or no reservation ID, nothing to cancel
        if (! $this->synced_to_platform || ! $this->platform_reservation_id) {
            return true; // Already in a state where there's nothing to cancel
        }

        // Check if this is a duplicate reservation
        $isDuplicate = isset($this->platform_data['is_duplicate']) && $this->platform_data['is_duplicate'];
        $actualReservationId = $isDuplicate
            ? $this->platform_data['original_platform_reservation_id']
            : $this->platform_reservation_id;

        if ($isDuplicate) {
            // For duplicates, check if there are other active reservations using the same CoverManager reservation
            $activeReservations = self::query()->where('platform_type', 'covermanager')
                ->where('platform_status', '!=', 'cancelled')
                ->where(function ($query) use ($actualReservationId) {
                    $query->where('platform_reservation_id', $actualReservationId)
                        ->orWhere('platform_data->original_platform_reservation_id', $actualReservationId);
                })
                ->where('id', '!=', $this->id)
                ->count();

            if ($activeReservations > 0) {
                // Don't cancel in CoverManager if other bookings are still using this reservation
                $this->update([
                    'platform_status' => 'cancelled',
                    'last_synced_at' => Carbon::now(),
                ]);

                Log::info("CoverManager duplicate reservation cancelled locally (not in platform) for reservation {$this->id} at {$this->venue->name}", [
                    'reservation_id' => $this->id,
                    'venue_id' => $this->venue->id,
                    'venue_name' => $this->venue->name,
                    'platform_reservation_id' => $this->platform_reservation_id,
                    'original_platform_reservation_id' => $actualReservationId,
                    'active_reservations_remaining' => $activeReservations,
                    'customer_name' => $this->platform_data['customer_name'] ?? '',
                ]);

                return true;
            }
        }

        $coverManagerService = app(CoverManagerService::class);
        if (! $coverManagerService) {
            return false;
        }

        // Get restaurant ID from venue platform configuration
        $platform = $this->venue->getPlatform('covermanager');
        if (! $platform || ! $platform->is_enabled) {
            Log::error("CoverManager platform not enabled for venue {$this->venue->id}");

            return false;
        }

        $restaurantId = $platform->getConfig('restaurant_id');
        if (! $restaurantId) {
            Log::error("Missing CoverManager restaurant ID for venue {$this->venue->id}");

            return false;
        }

        // Call the CoverManager API to cancel the reservation using the actual reservation ID
        $result = $coverManagerService->cancelReservationRaw($restaurantId, $actualReservationId);

        if ($result) {
            // Update this reservation record
            $this->update([
                'platform_status' => 'cancelled',
                'last_synced_at' => Carbon::now(),
            ]);

            // If this was the original reservation and there are duplicates, mark them as cancelled too
            if (! $isDuplicate) {
                $duplicateReservations = self::query()->where('platform_type', 'covermanager')
                    ->where('platform_data->original_platform_reservation_id', $actualReservationId)
                    ->where('id', '!=', $this->id)
                    ->get();

                foreach ($duplicateReservations as $duplicate) {
                    $duplicate->update([
                        'platform_status' => 'cancelled',
                        'last_synced_at' => Carbon::now(),
                    ]);

                    Log::info("CoverManager duplicate reservation auto-cancelled for reservation {$duplicate->id} (linked to cancelled original)", [
                        'duplicate_reservation_id' => $duplicate->id,
                        'original_reservation_id' => $this->id,
                        'platform_reservation_id' => $actualReservationId,
                    ]);
                }
            }

            Log::info("CoverManager cancel reservation successful for reservation {$this->id} at {$this->venue->name}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'restaurant_id' => $restaurantId,
                'platform_reservation_id' => $this->platform_reservation_id,
                'actual_reservation_id' => $actualReservationId,
                'is_duplicate' => $isDuplicate,
                'customer_name' => $this->platform_data['customer_name'] ?? '',
            ]);
        } else {
            Log::error("CoverManager cancel reservation failed for reservation {$this->id} at {$this->venue->name}: API error", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'restaurant_id' => $restaurantId,
                'platform_reservation_id' => $this->platform_reservation_id,
                'actual_reservation_id' => $actualReservationId,
                'is_duplicate' => $isDuplicate,
                'customer_name' => $this->platform_data['customer_name'] ?? '',
            ]);
        }

        return $result;
    }

    /**
     * Cancel this reservation in Restoo.
     */
    protected function cancelInRestoo(): bool
    {
        if (! $this->venue->hasPlatform('restoo')) {
            return false;
        }

        // If not synced or no reservation ID, nothing to cancel
        if (! $this->synced_to_platform || ! $this->platform_reservation_id) {
            return true; // Already in a state where there's nothing to cancel
        }

        $restooService = app(RestooService::class);
        if (! $restooService) {
            return false;
        }

        // Call the Restoo API to cancel the reservation
        $result = $restooService->cancelReservation($this->venue, $this->platform_reservation_id);

        if ($result) {
            // Update the reservation record
            $this->update([
                'platform_status' => 'cancelled',
                'last_synced_at' => Carbon::now(),
            ]);

            Log::info("Restoo cancel reservation successful for reservation {$this->id} at {$this->venue->name}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'platform_reservation_id' => $this->platform_reservation_id,
                'customer_name' => $this->platform_data['customer_name'] ?? '',
            ]);
        } else {
            Log::error("Restoo cancel reservation failed for reservation {$this->id} at {$this->venue->name}: API error", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'platform_reservation_id' => $this->platform_reservation_id,
                'customer_name' => $this->platform_data['customer_name'] ?? '',
            ]);
        }

        return $result;
    }

    /**
     * Get customer name from platform data.
     */
    protected function customerName(): Attribute
    {
        return Attribute::make(get: fn () => $this->platform_data['customer_name'] ?? null);
    }

    /**
     * Get party size from platform data.
     */
    protected function partySize(): Attribute
    {
        return Attribute::make(get: fn () => $this->platform_data['party_size'] ?? null);
    }

    /**
     * Get reservation datetime for display.
     */
    protected function reservationDatetime(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->platform_type === 'covermanager') {
                $date = $this->platform_data['reservation_date'] ?? null;
                $time = $this->platform_data['reservation_time'] ?? null;
                if ($date && $time) {
                    return Carbon::parse("{$date} {$time}");
                }
            } elseif ($this->platform_type === 'restoo') {
                $datetime = $this->platform_data['reservation_datetime'] ?? null;
                if ($datetime) {
                    return Carbon::parse($datetime);
                }
            }

            return null;
        });
    }
}
