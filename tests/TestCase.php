<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use App\Services\RestooService;
use App\Services\CoverManagerService;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // ABSOLUTELY PREVENT ALL EXTERNAL HTTP REQUESTS - NO EXCEPTIONS
        Http::preventStrayRequests();
        
        // Get API base URLs from environment configuration
        $coverManagerBaseUrl = config('services.covermanager.base_url');
        $restooBaseUrl = config('services.restoo.base_url');
        
        // Comprehensive HTTP mocking - block ALL possible external calls
        Http::fake([
            // CoverManager API - all possible endpoints
            $coverManagerBaseUrl . '/*' => Http::response([
                'resp' => 1, 
                'id_reserv' => 'test-mocked-cm-id',
                'status' => '1'
            ], 200),
            
            // CoverManager error scenarios for tests that expect failures
            $coverManagerBaseUrl . '/reserv/reserv' => Http::response([
                'resp' => 0,
                'error' => 'Hour Not Available'
            ], 400),
            
            // Restoo API - all possible endpoints
            $restooBaseUrl . '/*' => Http::response([
                'success' => true,
                'uuid' => 'test-mocked-restoo-uuid',
                'status' => 'confirmed'
            ], 200),
            
            // Block any other external URLs
            '*' => Http::response(['error' => 'External call blocked in tests'], 500),
        ]);
        
        // MOCK PLATFORM SERVICES TO PREVENT ANY REAL API CALLS
        $this->mock(RestooService::class, function ($mock) {
            $mock->shouldReceive('createReservation')
                 ->andReturn([
                     'uuid' => 'test-mocked-restoo-uuid',
                     'status' => 'confirmed',
                     'success' => true
                 ]);
            $mock->shouldReceive('cancelReservation')
                 ->andReturn(['success' => true]);
        });
        
        $this->mock(CoverManagerService::class, function ($mock) {
            $mock->shouldReceive('createReservation')
                 ->andReturn([
                     'resp' => 1,
                     'id_reserv' => 'test-mocked-cm-id',
                     'status' => '1'
                 ]);
            $mock->shouldReceive('cancelReservation')
                 ->andReturn(['resp' => 1]);
        });
    }
}
