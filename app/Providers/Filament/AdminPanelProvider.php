<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Auth\ResetPassword;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Exception;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jenssegers\Agent\Agent;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();
    }

    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $agent = new Agent();

        $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->passwordResetRoutePrefix('/')
            ->passwordResetRequestRouteSlug('password-reset')
            ->passwordResetRouteSlug('secure')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->navigationItems([

                NavigationItem::make('Horizon')
                    ->url('/horizon')
                    ->icon('heroicon-o-sun')
                    ->sort(1000)
                    ->visible(fn() => auth()->user()->email === 'andru.weir@gmail.com'),
                NavigationItem::make('Pulse')
                    ->url('/pulse')
                    ->icon('ri-pulse-line')
                    ->sort(999)
                    ->visible(fn() => auth()->user()->email === 'andru.weir@gmail.com'),

            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->favicon('/favicon.ico')
            ->darkMode(false)
            ->brandName('PRIMA')
            ->viteTheme('resources/css/filament/admin/theme.css');

        if (!$agent->isSafari()) {
            $panel->spa();
        }

        return $panel;
    }
}
