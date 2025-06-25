<?php

namespace App\Providers;

use App\Contracts\BookingPlatformInterface;
use App\Factories\BookingPlatformFactory;
use App\Services\CoverManagerService;
use Illuminate\Support\ServiceProvider;

class BookingPlatformServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the factory as a singleton
        $this->app->singleton(BookingPlatformFactory::class, fn ($app) => new BookingPlatformFactory($app));

        // Register default implementation of the interface
        $this->app->bind(BookingPlatformInterface::class, fn ($app) =>
            // By default, use CoverManager (can be configured differently)
            $app->make(CoverManagerService::class));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
