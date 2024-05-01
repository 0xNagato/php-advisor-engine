<?php

/** @noinspection JSUnresolvedReference, JSUnresolvedLibraryURL */

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isLocal()) {
            ($this->{'app'}['request'] ?? null)?->server?->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        LogViewer::auth(static function ($request) {
            return $request->user()
                && $request->user()->hasRole('super_admin');
        });

        FilamentColor::register([
            'indigo' => Color::Indigo,
            'brand' => '#4736dd',
            'gold' => '#DAA520',
            'silver' => '#A9A9A9',
        ]);

        Filament::registerRenderHook(
            'panels::body.end',
            static fn (): string => <<<'HTML'
                <div x-data="" x-init="
                    if (!localStorage.getItem('sidebar_initialized')) {
                        localStorage.setItem('sidebar_initialized', true);
                        $store.sidebar.isOpen = false;
                    }"
                    x-on:region-changed.window="$store.sidebar.isOpen = false"
                >
                </div>
            HTML
        );

        /**
         * @TODO Refactor into a blade component
         */
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            static fn () => new HtmlString("
                <script>
                const { userAgent } = window.navigator;
                if (/PrimaApp/.test(userAgent)) {
                    document.documentElement.classList.add('prima-native');
                }
                </script>
            ")
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            static fn () => view('filament.admin.logo')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            static fn () => new HtmlString('
            <div class="mb-4 text-xs text-center">
                &copy; 2024 PRIMA VIP. All rights reserved.
            </div>
            ')
        );

        Filament::registerRenderHook(
            'panels::head.start',
            static fn (): string => '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />',
        );

        Filament::registerRenderHook(PanelsRenderHook::HEAD_START, static function () {
            return new HtmlString("
                <!-- Google tag (gtag.js) -->
                <script async src='https://www.googletagmanager.com/gtag/js?id=G-Z8HQ7BTL4F'></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag('js', new Date());

                  gtag('config', 'G-Z8HQ7BTL4F');
                </script>
                <script
                    src='https://js.sentry-cdn.com/13f74541d55ad7fbd95d3eefa72399c9.min.js'
                    crossorigin='anonymous'
                ></script>
            ");
        });

        FilamentView::registerRenderHook(
            'panels::head.start',
            static fn (): string => '
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">',
        );

        Filament::registerRenderHook(
            // PanelsRenderHook::USER_MENU_PROFILE_AFTER,
            PanelsRenderHook::SIDEBAR_NAV_START,
            static fn () => view('partials.concierge-user-menu')
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
