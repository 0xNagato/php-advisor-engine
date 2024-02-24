<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::registerRenderHook(
            'panels::body.end',
            fn (): string => <<<'HTML'
                <div x-data="" x-init="
                    if (!localStorage.getItem('sidebar_initialized')) {
                        localStorage.setItem('sidebar_initialized', true);
                        $store.sidebar.isOpen = false;
                    }
                "></div>
            HTML
        );
    }
}
