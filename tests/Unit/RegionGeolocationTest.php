<?php

namespace Tests\Unit;

use App\Traits\HandlesRegionValidation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class RegionGeolocationTest extends TestCase
{
    use HandlesRegionValidation;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache to ensure fresh tests
        Cache::flush();
        
        // Set up mock config values
        Config::set('app.default_region', 'miami');
        Config::set('app.region_code_mapping', [
            'FL' => 'miami',
            'CA' => 'los_angeles',
            'IB' => 'ibiza',
        ]);
        Config::set('app.country_region_mapping', [
            'US' => 'miami',
            'ES' => 'ibiza',
            'CA' => 'los_angeles',
            'FR' => 'ibiza',
        ]);
    }

    public function test_resolves_miami_from_florida_ip(): void
    {
        // Mock IPAPI response for Florida
        Http::fake([
            'https://ipapi.co/*' => Http::response([
                'region_code' => 'FL',
                'country_code' => 'US',
            ], 200),
        ]);

        $region = $this->resolveRegion('203.0.113.1'); // Test IP
        $this->assertEquals('miami', $region);
    }

    public function test_resolves_ibiza_from_spain_ip(): void
    {
        // Mock IPAPI response for Spain
        Http::fake([
            'https://ipapi.co/*' => Http::response([
                'region_code' => 'MD', // Madrid
                'country_code' => 'ES',
            ], 200),
        ]);

        $region = $this->resolveRegion('203.0.113.2'); // Test IP
        $this->assertEquals('ibiza', $region);
    }

    public function test_resolves_ibiza_from_ibiza_region_code(): void
    {
        // Mock IPAPI response for Ibiza
        Http::fake([
            'https://ipapi.co/*' => Http::response([
                'region_code' => 'IB',
                'country_code' => 'ES',
            ], 200),
        ]);

        $region = $this->resolveRegion('203.0.113.3'); // Test IP
        $this->assertEquals('ibiza', $region);
    }

    public function test_falls_back_to_geojs_when_ipapi_fails(): void
    {
        // Mock IPAPI failure and GeoJS success
        Http::fake([
            'https://ipapi.co/*' => Http::response([], 429), // Rate limited
            'https://get.geojs.io/*' => Http::response([
                'region' => 'CA',
                'country_code' => 'US',
            ], 200),
        ]);

        $region = $this->resolveRegion('203.0.113.4'); // Test IP
        $this->assertEquals('los_angeles', $region);
    }

    public function test_returns_default_region_when_all_lookups_fail(): void
    {
        // Mock both services failing
        Http::fake([
            'https://ipapi.co/*' => Http::response([], 500),
            'https://get.geojs.io/*' => Http::response([], 500),
        ]);

        $region = $this->resolveRegion('203.0.113.5'); // Test IP
        $this->assertEquals('miami', $region);
    }
    
    public function test_resolves_los_angeles_from_canada_ip(): void
    {
        // Mock IPAPI response for Canada
        Http::fake([
            'https://ipapi.co/*' => Http::response([
                'region_code' => 'ON', // Ontario
                'country_code' => 'CA',
            ], 200),
        ]);

        $region = $this->resolveRegion('203.0.113.6'); // Test IP
        $this->assertEquals('los_angeles', $region);
    }
    
    public function test_resolves_ibiza_from_european_countries(): void
    {
        // Test a few European countries
        $europeanCountries = ['FR', 'IT', 'GB'];
        
        foreach ($europeanCountries as $index => $country) {
            // Mock IPAPI response for the country
            Http::fake([
                'https://ipapi.co/*' => Http::response([
                    'region_code' => 'XX', // Doesn't matter
                    'country_code' => $country,
                ], 200),
            ]);
            
            $ip = "203.0.113." . (10 + $index); // Different test IP for each country
            $region = $this->resolveRegion($ip);
            $this->assertEquals('ibiza', $region, "Country {$country} should map to ibiza");
        }
    }

    public function test_caches_result_for_same_ip(): void
    {
        // Mock IPAPI response
        Http::fake([
            'https://ipapi.co/*' => Http::response([
                'region_code' => 'CA',
                'country_code' => 'US',
            ], 200),
        ]);

        // First call should hit the API
        $region1 = $this->resolveRegion('203.0.113.6');
        $this->assertEquals('los_angeles', $region1);

        // Change the mock to return something different
        Http::fake([
            'https://ipapi.co/*' => Http::response([
                'region_code' => 'FL',
                'country_code' => 'US',
            ], 200),
        ]);

        // Second call with same IP should return cached result
        $region2 = $this->resolveRegion('203.0.113.6');
        $this->assertEquals('los_angeles', $region2);
    }
}