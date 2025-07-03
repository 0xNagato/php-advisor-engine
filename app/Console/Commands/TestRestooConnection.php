<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

class TestRestooConnection extends Command
{
    protected $signature = 'restoo:test {apiKey} {account}';

    protected $description = 'Test connection to Restoo API';

    public function handle()
    {
        $apiKey = $this->argument('apiKey');
        $account = $this->argument('account');
        $baseUrl = Config::get('services.restoo.base_url', 'https://integration-dev.myrestoo.net');
        $partnerId = Config::get('services.restoo.partner_id', 'prima');

        $this->info('Testing Restoo connection with:');
        $this->info("Base URL: {$baseUrl}");
        $this->info("Partner ID: {$partnerId}");
        $this->info("Account: {$account}");
        $this->info('API Key: '.substr($apiKey, 0, 5).'...');

        // Define a list of endpoints to try
        $endpoints = [
            "/api/{$partnerId}/v3/status",
            '/api/v3/status',  // Try without partner ID
            '/api/status',     // Try without version
            "/partners/{$partnerId}/v3/status", // Try different path structure
            '/v3/bookings/status', // Common API pattern
            '/v3/status', // Simple endpoint
        ];

        $foundValidEndpoint = false;

        foreach ($endpoints as $endpoint) {
            try {
                $url = $baseUrl.$endpoint;
                $this->info("\nTrying endpoint: {$url}");

                $response = Http::withHeaders([
                    'Account' => $account,
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->get($url);

                $this->info('Status code: '.$response->status());

                // Truncate the response body if it's too long
                $body = $response->body();
                if (strlen($body) > 300) {
                    $body = substr($body, 0, 300).'... (truncated)';
                }

                $this->info('Response body: '.$body);

                if ($response->status() !== 404) {
                    $foundValidEndpoint = true;

                    if ($response->successful()) {
                        $this->info('SUCCESS: This endpoint returned a successful response!');
                        // Break out of the loop if we found a successful endpoint
                        break;
                    } elseif (in_array($response->status(), [401, 403])) {
                        $this->warn('ENDPOINT FOUND: This endpoint appears to exist but returned an authorization error.');
                        // Break out of the loop if we found an endpoint that requires auth
                        break;
                    } else {
                        $this->warn('ENDPOINT FOUND: This endpoint returned a non-404 response but might not be valid for testing.');
                    }
                } else {
                    $this->error('ENDPOINT NOT FOUND: Got a 404 response for this endpoint.');
                }
            } catch (Throwable $e) {
                $this->error('Exception occurred: '.$e->getMessage());
            }
        }

        if ($foundValidEndpoint) {
            $this->info("\nFound at least one endpoint that might be valid. Check the results above.");

            return Command::SUCCESS;
        } else {
            $this->error("\nAll endpoints returned 404 or failed. The API URL might be incorrect or inaccessible.");

            return Command::FAILURE;
        }
    }
}
