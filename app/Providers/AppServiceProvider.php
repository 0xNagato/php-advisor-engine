<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'indigo' => Color::Indigo,
            'brand' => '#4736dd',
        ]);

        Filament::registerRenderHook(
            'panels::body.end',
            static fn (): string => <<<'HTML'
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
            static fn () => view('filament.admin.logo')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            static fn () => new HtmlString('
            <div class="text-xs text-center mb-4">
                &copy; 2024 PRIMA VIP
            </div>
            ')
        );

        Filament::registerRenderHook(
            'panels::head.start',
            static fn (): string => '<meta name="viewport" content="width=device-width, initial-scale=1" />',
        );

        FilamentView::registerRenderHook(
            'panels::head.start',
            static fn (): string => '<link href="https://db.onlinewebfonts.com/c/5b381abe79163202b03f53ed0eab3065?family=Sanomat+Web+Regular+Regular" rel="stylesheet">',
        );

        Page::$reportValidationErrorUsing = static function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
