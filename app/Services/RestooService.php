<?php

namespace App\Services;

use App\Contracts\BookingPlatformInterface;
use App\Models\Booking;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestooService implements BookingPlatformInterface
{
    protected string $baseUrl;

    protected string $partnerId;

    public function __construct()
    {
        $this->baseUrl = Config::get('services.restoo.base_url');
        $this->partnerId = Config::get('services.restoo.partner_id', 'prima');

    }

    /**
     * Get the platform name identifier
     */
    public function getPlatformName(): string
    {
        return 'restoo';
    }

    /**
     * Check availability for a specific venue, date, time and party size
     */
    public function checkAvailability(Venue $venue, Carbon $date, string $time, int $partySize): array
    {
        $platform = $venue->getPlatform('restoo');

        if (! $platform) {
            return [];
        }

        $apiKey = $platform->getConfig('api_key');
        $account = $platform->getConfig('account');

        if (blank($apiKey) || blank($account)) {
            return [];
        }

        return $this->checkAvailabilityRaw($apiKey, $account, $date, $time, $partySize);
    }

    /**
     * Create a reservation on the platform
     */
    public function createReservation(Venue $venue, Booking $booking): ?array
    {
        $platform = $venue->getPlatform('restoo');

        if (! $platform) {
            Log::error('No Restoo platform found for venue', ['venue_id' => $venue->id]);

            return null;
        }

        $apiKey = $platform->getConfig('api_key');
        $account = $platform->getConfig('account');

        if (blank($apiKey) || blank($account)) {
            Log::error('Missing Restoo credentials', [
                'venue_id' => $venue->id,
                'has_api_key' => ! blank($apiKey),
                'has_account' => ! blank($account),
            ]);

            return null;
        }

        // Use the booking_at timestamp directly (it already includes date and time)
        $bookingTime = Carbon::parse($booking->booking_at);

        // Set timezone to venue's timezone for proper formatting
        $venueTimezone = $venue->timezone ?? 'UTC';
        $bookingTime->setTimezone($venueTimezone);

        // Round to nearest 15 minutes as required by Restoo
        $minutes = $bookingTime->minute;
        $roundedMinutes = (int) round($minutes / 15) * 15;
        $bookingTime->minute($roundedMinutes)->second(0);

        $payload = [
            'bookingExternalId' => "Prima-{$booking->id}",
            'bookingAt' => $bookingTime->format('Y-m-d\TH:i:00').$bookingTime->format('P'),
            'pax' => $booking->guest_count,
            'customerSpecialRequests' => $booking->notes ?: '',
            'customer' => [
                'name' => $booking->guest_name,
                'email' => $booking->guest_email,
                'phoneE164' => $booking->guest_phone,
            ],
        ];

        try {
            $result = $this->createReservationRaw($apiKey, $account, $payload);

            return $result;
        } catch (Throwable $e) {
            Log::error('Exception in createReservationRaw', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Cancel a reservation on the platform
     */
    public function cancelReservation(Venue $venue, string $externalReservationId): bool
    {
        $platform = $venue->getPlatform('restoo');

        if (! $platform) {
            return false;
        }

        $apiKey = $platform->getConfig('api_key');
        $account = $platform->getConfig('account');

        if (blank($apiKey) || blank($account)) {
            return false;
        }

        return $this->cancelReservationRaw($apiKey, $account, $externalReservationId);
    }

    /**
     * Create a reservation on the platform bypassing availability checks (force booking)
     */
    public function createReservationForce(Venue $venue, Booking $booking): ?array
    {
        Log::warning('Force booking not supported by Restoo platform', [
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
            'platform' => 'restoo',
        ]);

        // Restoo does not support force booking - fall back to regular reservation creation
        return $this->createReservation($venue, $booking);
    }

    /**
     * Check availability for a specific date, time and party size (internal implementation)
     *
     * Note: Restoo availability endpoint TBD based on Nacho's response
     */
    protected function checkAvailabilityRaw(string $apiKey, string $account, Carbon $date, string $time, int $partySize): array
    {
        try {
            // Format datetime for Restoo
            $dateTime = $date->format('Y-m-d').'T'.$time.':00'.$date->format('P');

            // Use the working endpoint format, adapting it for availability
            $response = Http::withHeaders([
                'Account' => $account,
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/partners/{$this->partnerId}/v3/availability", [
                'bookingAt' => $dateTime,
                'pax' => $partySize,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Restoo availability check failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'dateTime' => $dateTime,
                'partySize' => $partySize,
            ]);

            return [];
        } catch (Throwable $e) {
            Log::error('Restoo availability check exception', [
                'error' => $e->getMessage(),
                'dateTime' => $date->format('Y-m-d').' '.$time,
                'partySize' => $partySize,
            ]);

            return [];
        }
    }

    /**
     * Create a reservation (internal implementation)
     */
    protected function createReservationRaw(string $apiKey, string $account, array $payload): ?array
    {
        try {
            $url = "{$this->baseUrl}/api/{$this->partnerId}/v3/booking";

            $response = Http::withHeaders([
                'Account' => $account,
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $responseData = $response->json() ?? [];

            // Add HTTP status and error information to the response
            $responseData['_http_status'] = $response->status();
            $responseData['_http_error'] = $response->failed() ? $response->body() : null;

            if ($response->successful()) {
                return $responseData;
            }

            // Removed duplicate error logging - errors are logged at the PlatformReservation level with more context
            return $responseData;
        } catch (Throwable $e) {
            // Removed duplicate error logging - errors are logged at the PlatformReservation level with more context
            return [
                '_http_status' => null,
                '_http_error' => $e->getMessage(),
                'exception' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a reservation (internal implementation)
     *
     * Updated per Restoo's latest API (June 2025):
     * - POST endpoint: /api/prima/v3/booking/{uuid}/cancel
     * - Requires payload with optional cancelReason
     * - Options: "BOOKED_ANOTHER_PLACE" | "CHANGED_PLANS" | "OTHER"
     */
    protected function cancelReservationRaw(string $apiKey, string $account, string $externalReservationId): bool
    {
        try {
            // Prepare cancellation payload per Restoo's API specification
            $payload = [
                'cancelReason' => 'OTHER', // Default reason, could be made configurable
            ];

            // Use POST request to the cancel endpoint per Restoo's specification
            $response = Http::withHeaders([
                'Account' => $account,
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/api/{$this->partnerId}/v3/booking/{$externalReservationId}/cancel", $payload);

            if ($response->successful()) {
                return true;
            }

            // Log cancellation failures with HTTP status code
            Log::error('Restoo reservation cancellation failed', [
                'http_status' => $response->status(),
                'response_body' => $response->body(),
                'external_reservation_id' => $externalReservationId,
                'payload' => $payload,
            ]);

            return false;
        } catch (Throwable $e) {
            // Log cancellation exceptions
            Log::error('Restoo reservation cancellation exception', [
                'error' => $e->getMessage(),
                'external_reservation_id' => $externalReservationId,
            ]);

            return false;
        }
    }
}
