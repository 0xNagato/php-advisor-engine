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

        // Log the configuration for debugging
        Log::debug('Restoo service initialized', [
            'baseUrl' => $this->baseUrl,
            'partnerId' => $this->partnerId,
        ]);
    }

    /**
     * Get the platform name identifier
     */
    public function getPlatformName(): string
    {
        return 'restoo';
    }

    /**
     * Check if the platform credentials for the venue are valid
     */
    public function checkAuth(Venue $venue): bool
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

        return $this->checkCredentialsValid($apiKey, $account);
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
        Log::debug('RestooService::createReservation called', [
            'venue_id' => $venue->id,
            'booking_id' => $booking->id,
        ]);

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

        Log::debug('Parsed booking time', [
            'original' => $booking->booking_at,
            'parsed' => $bookingTime->toDateTimeString(),
            'timezone' => $venueTimezone,
        ]);

        // Round to nearest 15 minutes as required by Restoo
        $minutes = $bookingTime->minute;
        $roundedMinutes = round($minutes / 15) * 15;
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

        Log::debug('Restoo reservation payload', ['payload' => $payload]);

        try {
            Log::debug('About to call createReservationRaw');
            $result = $this->createReservationRaw($apiKey, $account, $payload);
            Log::debug('createReservationRaw completed', ['result' => $result]);

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
     * Test if provided API credentials are valid
     * This method doesn't require a venue and can be used during setup
     */
    public function testCredentials(string $apiKey, string $account): bool
    {
        return $this->checkCredentialsValid($apiKey, $account);
    }

    /**
     * Check if the API credentials are valid (internal implementation)
     */
    protected function checkCredentialsValid(string $apiKey, string $account): bool
    {
        try {
            // Use the endpoint we found that works
            $url = "{$this->baseUrl}/partners/{$this->partnerId}/v3/status";

            // Log the request details
            Log::debug('Restoo testing credentials', [
                'url' => $url,
                'account' => $account,
                'apiKey' => substr($apiKey, 0, 5).'...',
            ]);

            // Make API call with credentials
            $response = Http::withHeaders([
                'Account' => $account,
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->get($url);

            Log::debug('Restoo API response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // If we get a 200 response, that means the endpoint is accessible
            // We should check if there's specific error content in the HTML response
            // that might indicate invalid credentials
            if ($response->status() === 200) {
                $body = $response->body();

                // Check if the response body contains error messages related to authentication
                if (str_contains($body, 'Invalid credentials') ||
                    str_contains($body, 'Authentication failed') ||
                    str_contains($body, 'Unauthorized')) {
                    Log::warning('Restoo credentials appear invalid based on response content', [
                        'status' => $response->status(),
                    ]);

                    return false;
                }

                // For now, assume the credentials are valid if we get a 200 response
                return true;
            }

            // If we get a 401 or 403, that specifically means the credentials are invalid
            if (in_array($response->status(), [401, 403])) {
                Log::warning('Restoo credentials are invalid', [
                    'status' => $response->status(),
                ]);

                return false;
            }

            // For other response codes, assume there's a configuration issue
            Log::error('Restoo authentication check failed with unexpected status code', [
                'status' => $response->status(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('Restoo authentication failed with exception', [
                'error' => $e->getMessage(),
                'account' => $account,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
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
        Log::debug('createReservationRaw method called', [
            'apiKey' => substr($apiKey, 0, 5).'...',
            'account' => $account,
            'payload_size' => count($payload),
        ]);

        try {
            $url = "{$this->baseUrl}/api/{$this->partnerId}/v3/booking";

            Log::debug('Making Restoo API request', [
                'url' => $url,
                'account' => $account,
                'api_key' => substr($apiKey, 0, 5).'...',
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Account' => $account,
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            Log::debug('Restoo API response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Restoo reservation created successfully', [
                    'response' => $responseData,
                ]);

                return $responseData;
            }

            Log::error('Restoo reservation creation failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload,
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('Restoo reservation creation exception', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return null;
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
                Log::info('Restoo reservation cancelled successfully', [
                    'externalReservationId' => $externalReservationId,
                    'cancelReason' => $payload['cancelReason'],
                ]);

                return true;
            }

            Log::error('Restoo reservation cancellation failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'externalReservationId' => $externalReservationId,
                'payload' => $payload,
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('Restoo reservation cancellation exception', [
                'error' => $e->getMessage(),
                'externalReservationId' => $externalReservationId,
            ]);

            return false;
        }
    }
}
