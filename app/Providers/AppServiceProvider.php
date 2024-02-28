<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::registerRenderHook(
            'panels::body.end',
            static fn(): string => <<<'HTML'
                <div x-data="" x-init="
                    if (!localStorage.getItem('sidebar_initialized')) {
                        localStorage.setItem('sidebar_initialized', true);
                        $store.sidebar.isOpen = false;
                    }
                "></div>
            HTML
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            static fn() => view('filament.admin.logo')
        );


        // FilamentView::registerRenderHook(
        //     'panels::head.start',
        //     static fn(): string => '<link href="https://db.onlinewebfonts.com/c/5b381abe79163202b03f53ed0eab3065?family=Sanomat+Web+Regular+Regular" rel="stylesheet">',
        // );
        //
        // FilamentView::registerRenderHook(
        //     'panels::head.start',
        //     static fn(): string => '<link href="https://db.onlinewebfonts.com/c/e48e18fc2422049eaba9fc43548b664b?family=Melete+Bold" rel="stylesheet" type="text/css"/>',
        // );

        Page::$reportValidationErrorUsing = static function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };
    }
}
