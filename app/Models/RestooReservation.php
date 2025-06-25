<?php

namespace App\Models;

use App\Services\RestooService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestooReservation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'venue_id',
        'booking_id',
        'restoo_reservation_id',
        'restoo_status',
        'reservation_datetime',
        'party_size',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'restoo_response',
        'synced_to_restoo',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reservation_datetime' => 'datetime',
            'synced_to_restoo' => 'boolean',
            'last_synced_at' => 'datetime',
            'restoo_response' => 'array',
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
     * Create a Restoo reservation from a booking.
     */
    public static function createFromBooking(Booking $booking): ?self
    {
        $venue = $booking->venue;

        if (! $venue->hasPlatform('restoo')) {
            return null;
        }

        // Format date and time per Restoo requirements
        $bookingTime = $booking->booking_at;

        // Create a new Restoo reservation
        return self::query()->create([
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
            'reservation_datetime' => $bookingTime,
            'party_size' => $booking->guest_count,
            'customer_name' => $booking->guest_name,
            'customer_email' => $booking->guest_email,
            'customer_phone' => $booking->guest_phone,
            'notes' => $booking->notes,
            'synced_to_restoo' => false,
        ]);
    }

    /**
     * Sync this reservation to Restoo.
     */
    public function syncToRestoo(): bool
    {
        \Log::debug('RestooReservation::syncToRestoo called', [
            'reservation_id' => $this->id,
            'booking_id' => $this->booking_id,
        ]);

        $venue = $this->venue;

        if (! $venue->hasPlatform('restoo')) {
            \Log::warning('Venue does not have Restoo platform', [
                'venue_id' => $venue?->id,
                'reservation_id' => $this->id,
            ]);

            return false;
        }

        $booking = $this->booking;

        if (! $booking) {
            \Log::error('No booking found for reservation', [
                'reservation_id' => $this->id,
                'booking_id' => $this->booking_id,
            ]);

            return false;
        }

        $restooService = app(RestooService::class);

        if (! $restooService) {
            \Log::error('Failed to instantiate RestooService');

            return false;
        }

        \Log::debug('Calling RestooService::createReservation', [
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
        ]);

        // Call the Restoo API
        $response = $restooService->createReservation($venue, $booking);

        \Log::debug('RestooService::createReservation response', [
            'response' => $response,
            'is_null' => $response === null,
        ]);

        if (! $response) {
            \Log::error('RestooService::createReservation returned null', [
                'venue_id' => $venue->id,
                'booking_id' => $booking->id,
            ]);

            return false;
        }

        // Update the Restoo reservation record
        $this->update([
            'restoo_reservation_id' => $response['uuid'] ?? null,
            'restoo_status' => $response['status'] ?? 'unknown',
            'restoo_response' => $response,
            'synced_to_restoo' => true,
            'last_synced_at' => Carbon::now(),
        ]);

        \Log::info('Restoo reservation synced successfully', [
            'reservation_id' => $this->id,
            'booking_id' => $booking->id,
            'external_id' => $response['uuid'] ?? null,
        ]);

        return true;
    }

    /**
     * Cancel this reservation in Restoo.
     */
    public function cancelInRestoo(): bool
    {
        if (! $this->venue->hasPlatform('restoo')) {
            return false;
        }

        // If not synced or no reservation ID, nothing to cancel
        if (! $this->synced_to_restoo || ! $this->restoo_reservation_id) {
            return true; // Already in a state where there's nothing to cancel
        }

        $restooService = app(RestooService::class);

        if (! $restooService) {
            return false;
        }

        // Call the Restoo API to cancel the reservation
        $result = $restooService->cancelReservation($this->venue, $this->restoo_reservation_id);

        if ($result) {
            // Update the Restoo reservation record
            $this->update([
                'restoo_status' => 'cancelled',
                'last_synced_at' => Carbon::now(),
            ]);
        }

        return $result;
    }
}
