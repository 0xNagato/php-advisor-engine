<?php

namespace App\Http\Integrations\Twilio;

use App\Http\Integrations\Twilio\Requests\LookupPhoneNumber;
use Exception;
use Log;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class TwilioLookup extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL for the Lookup API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://lookups.twilio.com/v2';
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator(
            username: config('services.twilio.sid'),
            password: config('services.twilio.token')
        );
    }

    /**
     * Validate a phone number using Twilio's Lookup v2 API
     *
     * @param  array  $fields  Additional data packages to include (e.g., 'line_type_intelligence')
     * @return array|null Returns the lookup data if successful, null if failed
     *
     * @throws Exception
     */
    public function lookupPhoneNumber(string $phone, array $fields = []): ?array
    {
        try {
            $response = $this->send(new LookupPhoneNumber($phone, $fields));

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Phone number lookup successful', [
                    'phone' => $phone,
                    'valid' => $data['valid'] ?? false,
                    'provider' => 'TWILIO_LOOKUP',
                ]);

                return $data;
            }

            Log::warning('Phone number lookup failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'provider' => 'TWILIO_LOOKUP',
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Phone number lookup error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'provider' => 'TWILIO_LOOKUP',
            ]);

            return null;
        }
    }

    /**
     * Check if a phone number is valid using basic lookup
     */
    public function isValidPhoneNumber(string $phone): bool
    {
        $lookupData = $this->lookupPhoneNumber($phone);

        return $lookupData !== null && ($lookupData['valid'] ?? false);
    }
}
