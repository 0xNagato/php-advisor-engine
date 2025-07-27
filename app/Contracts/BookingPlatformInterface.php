<?php

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Venue;
use Carbon\Carbon;

interface BookingPlatformInterface
{
    /**
     * Check availability for a specific venue, date, time and party size
     */
    public function checkAvailability(Venue $venue, Carbon $date, string $time, int $partySize): array;

    /**
     * Create a reservation on the platform
     */
    public function createReservation(Venue $venue, Booking $booking): ?array;

    /**
     * Cancel a reservation on the platform
     */
    public function cancelReservation(Venue $venue, string $externalReservationId): bool;

    /**
     * Create a reservation on the platform bypassing availability checks (force booking)
     */
    public function createReservationForce(Venue $venue, Booking $booking): ?array;

    /**
     * Get platform name identifier
     */
    public function getPlatformName(): string;
}
