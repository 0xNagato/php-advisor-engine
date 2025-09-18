<?php

namespace App\Models;

use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * @mixin IdeHelperCoverManagerReservation
 */
class CoverManagerReservation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'venue_id',
        'booking_id',
        'covermanager_reservation_id',
        'covermanager_status',
        'reservation_date',
        'reservation_time',
        'party_size',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'covermanager_response',
        'synced_to_covermanager',
        'last_synced_at',
    ];

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
     * Create a CoverManager reservation from a booking.
     */
    public static function createFromBooking(Booking $booking): ?self
    {
        $venue = $booking->schedule->venue;

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
            'time' => $booking->booking_at->format('H:i:s'),
            'size' => $booking->guest_count,
            'notes' => $booking->notes,
        ];

        // Call the CoverManager API first
        $response = $coverManagerService->createReservationRaw($restaurantId, $bookingData);

        if (! $response || ! isset($response['id_reserv'])) {
            $errorMessage = 'Unknown error';

            if ($response && isset($response['status'])) {
                $errorMessage = $response['status'];
            } elseif ($response && isset($response['error'])) {
                $errorMessage = $response['error'];
            } elseif (! $response) {
                $errorMessage = 'No response from CoverManager API';
            }

            Log::error("CoverManager create reservation failed for booking {$booking->id} at {$venue->name}: {$errorMessage}", [
                'booking_id' => $booking->id,
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'restaurant_id' => $restaurantId,
                'booking_data' => $bookingData,
                'api_response' => $response,
                'error_summary' => $errorMessage,
            ]);

            return null;
        }

        // Only create the database record after successful API call
        $reservation = self::query()->create([
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
            'covermanager_reservation_id' => $response['id_reserv'],
            'covermanager_status' => $response['status'] ?? 'unknown',
            'reservation_date' => $booking->booking_at,
            'reservation_time' => $booking->booking_at->format('H:i:s'),
            'party_size' => $booking->guest_count,
            'customer_name' => $booking->guest_name,
            'customer_email' => $booking->guest_email,
            'customer_phone' => $booking->guest_phone,
            'notes' => $booking->notes,
            'covermanager_response' => $response,
            'synced_to_covermanager' => true,
            'last_synced_at' => Carbon::now(),
        ]);

        Log::info("CoverManager create reservation successful for booking {$booking->id} at {$venue->name}", [
            'booking_id' => $booking->id,
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'restaurant_id' => $restaurantId,
            'covermanager_reservation_id' => $response['id_reserv'],
            'reservation_date' => $booking->booking_at->format('Y-m-d'),
            'reservation_time' => $booking->booking_at->format('H:i'),
            'party_size' => $booking->guest_count,
            'customer_name' => $booking->guest_name,
            'status' => $response['status'] ?? 'unknown',
        ]);

        return $reservation;
    }

    /**
     * Sync this reservation to CoverManager.
     */
    public function syncToCoverManager(): bool
    {
        // If already synced, return true
        if ($this->synced_to_covermanager && $this->covermanager_reservation_id) {
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

        // Format data for CoverManager API
        $bookingData = [
            'name' => $this->customer_name,
            'email' => $this->customer_email,
            'phone' => $this->customer_phone,
            'date' => $this->reservation_date->format('Y-m-d'),
            'time' => $this->reservation_time,
            'size' => $this->party_size,
            'notes' => $this->notes,
        ];

        // Call the CoverManager API
        $response = $coverManagerService->createReservationRaw($restaurantId, $bookingData);

        if (! $response || ! isset($response['id_reserv'])) {
            $errorMessage = 'Unknown error';

            if ($response && isset($response['status'])) {
                $errorMessage = $response['status'];
            } elseif ($response && isset($response['error'])) {
                $errorMessage = $response['error'];
            } elseif (! $response) {
                $errorMessage = 'No response from CoverManager API';
            }

            Log::error("CoverManager sync reservation failed for reservation {$this->id} at {$this->venue->name}: {$errorMessage}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'restaurant_id' => $restaurantId,
                'booking_data' => $bookingData,
                'api_response' => $response,
                'error_summary' => $errorMessage,
            ]);

            return false;
        }

        // Update the CoverManager reservation record
        $this->update([
            'covermanager_reservation_id' => $response['id_reserv'],
            'covermanager_status' => $response['status'] ?? 'unknown',
            'covermanager_response' => $response,
            'synced_to_covermanager' => true,
            'last_synced_at' => Carbon::now(),
        ]);

        Log::info("CoverManager sync reservation successful for reservation {$this->id} at {$this->venue->name}", [
            'reservation_id' => $this->id,
            'venue_id' => $this->venue->id,
            'venue_name' => $this->venue->name,
            'restaurant_id' => $restaurantId,
            'covermanager_reservation_id' => $response['id_reserv'],
            'reservation_date' => $this->reservation_date->format('Y-m-d'),
            'reservation_time' => $this->reservation_time,
            'party_size' => $this->party_size,
            'customer_name' => $this->customer_name,
            'status' => $response['status'] ?? 'unknown',
        ]);

        return true;
    }

    /**
     * Cancel this reservation in CoverManager.
     */
    public function cancelInCoverManager(): bool
    {
        if (! $this->venue->usesCoverManager()) {
            return false;
        }

        // If not synced or no reservation ID, nothing to cancel
        if (! $this->synced_to_covermanager || ! $this->covermanager_reservation_id) {
            return true; // Already in a state where there's nothing to cancel
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

        // Call the CoverManager API to cancel the reservation
        $result = $coverManagerService->cancelReservationRaw($restaurantId, $this->covermanager_reservation_id);

        if ($result) {
            // Update the CoverManager reservation record
            $this->update([
                'covermanager_status' => 'cancelled',
                'last_synced_at' => Carbon::now(),
            ]);

            Log::info("CoverManager cancel reservation successful for reservation {$this->id} at {$this->venue->name}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'restaurant_id' => $restaurantId,
                'covermanager_reservation_id' => $this->covermanager_reservation_id,
                'customer_name' => $this->customer_name,
            ]);
        } else {
            Log::error("CoverManager cancel reservation failed for reservation {$this->id} at {$this->venue->name}", [
                'reservation_id' => $this->id,
                'venue_id' => $this->venue->id,
                'venue_name' => $this->venue->name,
                'restaurant_id' => $restaurantId,
                'covermanager_reservation_id' => $this->covermanager_reservation_id,
                'customer_name' => $this->customer_name,
            ]);
        }

        return $result;
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reservation_date' => 'datetime',
            'synced_to_covermanager' => 'boolean',
            'last_synced_at' => 'datetime',
            'covermanager_response' => 'array',
        ];
    }
}
