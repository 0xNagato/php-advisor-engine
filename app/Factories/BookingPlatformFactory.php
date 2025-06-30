<?php

namespace App\Factories;

use App\Contracts\BookingPlatformInterface;
use App\Models\Venue;
use App\Services\CoverManagerService;
use App\Services\RestooService;
use Illuminate\Contracts\Container\Container;

class BookingPlatformFactory
{
    public function __construct(protected Container $container) {}

    /**
     * Get the appropriate booking platform service for a venue
     */
    public function getPlatformForVenue(Venue $venue): ?BookingPlatformInterface
    {
        // Check for enabled platforms on the venue
        $platform = $venue->platforms()
            ->where('is_enabled', true)
            ->first();

        if (! $platform) {
            return null;
        }

        return match ($platform->platform_type) {
            'covermanager' => $this->container->make(CoverManagerService::class),
            'restoo' => $this->container->make(RestooService::class),
            default => null,
        };
    }

    /**
     * Get a platform service by type
     */
    public function getPlatformByType(string $platformType): ?BookingPlatformInterface
    {
        return match ($platformType) {
            'covermanager' => $this->container->make(CoverManagerService::class),
            'restoo' => $this->container->make(RestooService::class),
            default => null,
        };
    }
}
