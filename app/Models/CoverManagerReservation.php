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
        $venue = $booking->scheduleTemplate->venue;

        if (! $venue->usesCoverManager()) {
            return null;
        }

        // Create a new CoverManager reservation
        return self::query()->create([
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
            'reservation_date' => $booking->booking_date,
            'reservation_time' => $booking->scheduleTemplate->start_time,
            'party_size' => $booking->party_size,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'notes' => $booking->notes,
            'synced_to_covermanager' => false,
        ]);
    }

    /**
     * Sync this reservation to CoverManager.
     */
    public function syncToCoverManager(): bool
    {
        if (! $this->venue->usesCoverManager()) {
            return false;
        }

        $coverManagerService = app(CoverManagerService::class);

        if (! $coverManagerService) {
            return false;
        }

        // Get restaurant ID from venue
        $restaurantId = $this->venue->covermanager_id;

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
        $response = $coverManagerService->createReservation($restaurantId, $bookingData);

        if (! $response) {
            return false;
        }

        // Update the CoverManager reservation record
        $this->update([
            'covermanager_reservation_id' => $response['id'] ?? null,
            'covermanager_status' => $response['status'] ?? 'unknown',
            'covermanager_response' => $response,
            'synced_to_covermanager' => true,
            'last_synced_at' => Carbon::now(),
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

        // Get restaurant ID from venue
        $restaurantId = $this->venue->covermanager_id;

        if (! $restaurantId) {
            Log::error("Missing CoverManager restaurant ID for venue {$this->venue->id}");

            return false;
        }

        // Call the CoverManager API to cancel the reservation
        $result = $coverManagerService->cancelReservation($restaurantId, $this->covermanager_reservation_id);

        if ($result) {
            // Update the CoverManager reservation record
            $this->update([
                'covermanager_status' => 'cancelled',
                'last_synced_at' => Carbon::now(),
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
