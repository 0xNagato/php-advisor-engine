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

/**
 * CoverManager API Service
 *
 * IMPORTANT: This service has been updated with the correct endpoint patterns
 * discovered through API testing with CoverManager support assistance.
 *
 * Current Status:
 * ✅ Working: Restaurant list (/restaurant/list/{apiKey}/)
 * ✅ Working: Availability check (POST /reserv/availability with ApiKey header)
 * 🔧 Testing: Reservation creation (POST /reserv/create with ApiKey header)
 * 🔧 Testing: Reservation cancellation (POST /reserv/cancel with ApiKey header)
 *
 * Key Discovery: API key must be sent in header, not URL path.
 */
class CoverManagerService implements BookingPlatformInterface
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $environment;

    public function __construct()
    {
        $this->baseUrl = Config::get('services.covermanager.base_url');
        $this->apiKey = Config::get('services.covermanager.api_key');
        $this->environment = Config::get('services.covermanager.environment', 'beta');

        // Log the configuration for debugging
        Log::debug('CoverManager service initialized', [
            'baseUrl' => $this->baseUrl,
            'environment' => $this->environment,
        ]);
    }

    /**
     * Get the platform name identifier
     */
    public function getPlatformName(): string
    {
        return 'covermanager';
    }

    /**
     * Check if the platform credentials for the venue are valid
     */
    public function checkAuth(Venue $venue): bool
    {
        // Get CoverManager restaurant ID from venue platform configuration
        $platform = $venue->getPlatform('covermanager');

        if (! $platform || ! $platform->is_enabled) {
            return false;
        }

        $restaurantId = $platform->getConfig('restaurant_id');

        if (blank($restaurantId)) {
            return false;
        }

        return $this->checkCredentialsValid();
    }

    /**
     * Check availability for a specific venue, date, time and party size
     */
    public function checkAvailability(Venue $venue, Carbon $date, string $time, int $partySize): array
    {
        // Get CoverManager restaurant ID from venue platform configuration
        $platform = $venue->getPlatform('covermanager');

        if (! $platform || ! $platform->is_enabled) {
            return [];
        }

        $restaurantId = $platform->getConfig('restaurant_id');

        if (blank($restaurantId)) {
            return [];
        }

        return $this->checkAvailabilityRaw($restaurantId, $date, $time, $partySize);
    }

    /**
     * Create a reservation on the platform
     */
    public function createReservation(Venue $venue, Booking $booking): ?array
    {
        // Get CoverManager restaurant ID from venue platform configuration
        $platform = $venue->getPlatform('covermanager');

        if (! $platform || ! $platform->is_enabled) {
            return null;
        }

        $restaurantId = $platform->getConfig('restaurant_id');

        if (blank($restaurantId)) {
            return null;
        }

        // Get the schedule template to access start_time
        $scheduleTemplate = \App\Models\ScheduleTemplate::find($booking->schedule_template_id);

        // Format data for CoverManager API
        $bookingData = [
            'name' => $booking->guest_name,
            'email' => $booking->guest_email,
            'phone' => $booking->guest_phone,
            'date' => $booking->booking_at->format('Y-m-d'),
            'time' => $scheduleTemplate->start_time,
            'size' => $booking->guest_count,
            'notes' => $booking->notes ?? '',
        ];

        return $this->createReservationRaw($restaurantId, $bookingData);
    }

    /**
     * Cancel a reservation on the platform
     */
    public function cancelReservation(Venue $venue, string $externalReservationId): bool
    {
        // Get CoverManager restaurant ID from venue platform configuration
        $platform = $venue->getPlatform('covermanager');

        if (! $platform || ! $platform->is_enabled) {
            return false;
        }

        $restaurantId = $platform->getConfig('restaurant_id');

        if (blank($restaurantId)) {
            return false;
        }

        return $this->cancelReservationRaw($restaurantId, $externalReservationId);
    }

    /**
     * Test if a restaurant ID is valid
     * This method can be used directly without requiring a venue
     *
     * @param  string  $restaurantId  The CoverManager restaurant ID to test
     * @return bool Whether the restaurant ID is valid
     */
    public function testRestaurantId(string $restaurantId): bool
    {
        try {
            $result = $this->getRestaurantData($restaurantId);

            return ! blank($result);
        } catch (Throwable $e) {
            Log::error('CoverManager restaurant ID test failed', [
                'error' => $e->getMessage(),
                'restaurantId' => $restaurantId,
            ]);

            return false;
        }
    }

    /**
     * Make a centralized API call with consistent logging and error handling
     *
     * @param  string  $method  HTTP method (GET, POST, etc.)
     * @param  string  $endpoint  API endpoint path
     * @param  array  $data  Request data for POST requests
     * @param  array  $headers  Additional headers
     * @param  string  $operationName  Human-readable operation name for logging
     * @return array|null Response data or null on failure
     */
    protected function makeApiCall(string $method, string $endpoint, array $data = [], array $headers = [], string $operationName = ''): ?array
    {
        try {
            $url = $this->baseUrl.$endpoint;
            $defaultHeaders = ['Content-Type' => 'application/json'];
            $requestHeaders = array_merge($defaultHeaders, $headers);

            // Make the HTTP request
            $response = match (strtoupper($method)) {
                'GET' => Http::withHeaders($requestHeaders)->get($url),
                'POST' => Http::withHeaders($requestHeaders)->post($url, $data),
                'PUT' => Http::withHeaders($requestHeaders)->put($url, $data),
                'DELETE' => Http::withHeaders($requestHeaders)->delete($url),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
            };

            // Log the API call details
            $logData = [
                'operation' => $operationName ?: $method.' '.$endpoint,
                'url' => $url,
                'method' => strtoupper($method),
                'status' => $response->status(),
                'response' => $response->json(),
            ];

            // Add request data to log for POST/PUT requests
            if (! empty($data)) {
                $logData['request_data'] = $data;
            }

            Log::info('CoverManager API Response - '.($operationName ?: 'API Call'), $logData);

            if ($response->successful()) {
                return $response->json();
            }

            // Log failed requests
            Log::error('CoverManager API request failed', [
                'operation' => $operationName,
                'url' => $url,
                'method' => strtoupper($method),
                'status' => $response->status(),
                'response' => $response->body(),
                'request_data' => $data,
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('CoverManager API exception', [
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'url' => $this->baseUrl.$endpoint,
                'method' => strtoupper($method),
                'request_data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Get restaurant list by city (platform-specific method)
     */
    public function getRestaurants(string $city = ''): array
    {
        $response = $this->makeApiCall(
            'GET',
            "/restaurant/list/{$this->apiKey}/{$city}",
            operationName: 'Get Restaurants'
        );

        return $response ?? [];
    }

    /**
     * Get restaurant data by restaurant ID (platform-specific method)
     */
    public function getRestaurantData(string $restaurantId): array
    {
        $response = $this->makeApiCall(
            'GET',
            "/restaurant/get/{$this->apiKey}/{$restaurantId}",
            operationName: 'Get Restaurant Data'
        );

        return $response ?? [];
    }

    /**
     * Check availability for a specific date, time and party size (internal implementation)
     */
    public function checkAvailabilityRaw(string $restaurantId, Carbon $date, string $time, int $partySize): array
    {
        $requestData = [
            'restaurant' => $restaurantId,
            'date' => $date->format('Y-m-d'),
            'discount' => 'all',
            'product_type' => '0',
            'number_people' => (string) $partySize,
        ];

        $response = $this->makeApiCall(
            'POST',
            '/reserv/availability',
            $requestData,
            ['apikey' => $this->apiKey],
            'Check Availability'
        );

        // Handle CoverManager's error response format
        if ($response && isset($response['resp']) && $response['resp'] === 0) {
            Log::error('CoverManager availability API error', [
                'error' => $response['error'] ?? $response['status'] ?? 'Unknown error',
                'restaurantId' => $restaurantId,
                'date' => $date->format('Y-m-d'),
                'time' => $time,
                'partySize' => $partySize,
                'requestData' => $requestData,
            ]);

            return [];
        }

        return $response ?? [];
    }

    /**
     * Create a reservation in CoverManager (internal implementation)
     */
    public function createReservationRaw(string $restaurantId, array $bookingData): ?array
    {
        // Split name into first_name and last_name
        $nameParts = explode(' ', $bookingData['name'], 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        $requestData = [
            'restaurant' => $restaurantId,
            'date' => $bookingData['date'],
            'hour' => $bookingData['time'],
            'people' => (string) $bookingData['size'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $bookingData['email'],
            'int_call_code' => '34', // Default to Spain, should be configurable
            'phone' => $bookingData['phone'],
            'source' => 'primavip',
            'commentary' => $bookingData['notes'] ?? '',
            'pending' => '0',
            'discount' => '0',
            'not_notify' => '0',
        ];

        $response = $this->makeApiCall(
            'POST',
            '/reserv/reserv',
            $requestData,
            ['apikey' => $this->apiKey],
            'Create Reservation'
        );

        // Handle CoverManager's error response format
        if ($response && isset($response['resp']) && $response['resp'] === 0) {
            $errorMessage = $response['error'] ?? $response['status'] ?? 'Unknown error';

            if ($errorMessage === 'Action not permited') {
                Log::warning('CoverManager reservation creation - API key lacks permissions', [
                    'error' => $errorMessage,
                    'restaurantId' => $restaurantId,
                    'message' => 'API key does not have reservation creation permissions. Contact CoverManager support to enable this feature.',
                ]);
            } else {
                Log::error('CoverManager reservation creation API error', [
                    'error' => $errorMessage,
                    'restaurantId' => $restaurantId,
                    'bookingData' => $bookingData,
                    'requestData' => $requestData,
                ]);
            }

            return null;
        }

        return $response;
    }

    /**
     * Cancel a reservation in CoverManager (internal implementation)
     */
    public function cancelReservationRaw(string $restaurantId, string $reservationId): bool
    {
        $requestData = [
            'id_reserv' => $reservationId,
            'headerFormat' => 0,
        ];

        $response = $this->makeApiCall(
            'POST',
            '/reserv/cancel_client',
            $requestData,
            ['apikey' => $this->apiKey],
            'Cancel Reservation'
        );

        // Handle CoverManager's error response format
        if ($response && isset($response['resp']) && $response['resp'] === 0) {
            $errorMessage = $response['error'] ?? $response['status'] ?? 'Unknown error';

            if ($errorMessage === 'Action not permited') {
                Log::warning('CoverManager reservation cancellation - API key lacks permissions', [
                    'error' => $errorMessage,
                    'reservationId' => $reservationId,
                    'message' => 'API key does not have reservation cancellation permissions. Contact CoverManager support to enable this feature.',
                ]);
            } else {
                Log::error('CoverManager reservation cancellation API error', [
                    'error' => $errorMessage,
                    'reservationId' => $reservationId,
                    'requestData' => $requestData,
                ]);
            }

            return false;
        }

        if ($response) {
            Log::info('CoverManager reservation cancelled successfully', [
                'reservationId' => $reservationId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if the API credentials are valid (internal implementation)
     */
    protected function checkCredentialsValid(): bool
    {
        $response = $this->makeApiCall(
            'GET',
            "/restaurant/list/{$this->apiKey}/",
            operationName: 'Check Credentials'
        );

        return $response !== null;
    }
}
