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
use Filament\Navigation\MenuItem;
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
use Stephenjude\FilamentDebugger\DebuggerPlugin;

class AdminPanelProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('/platform')
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
                    ->visible(fn () => auth()->user()->email === 'andru.weir@gmail.com'),
                NavigationItem::make('Pulse')
                    ->url('/pulse')
                    ->icon('ri-pulse-line')
                    ->sort(999)
                    ->visible(fn () => auth()->user()->email === 'andru.weir@gmail.com'),
                NavigationItem::make('Logs')
                    ->url('/log-viewer')
                    ->icon('gmdi-list-o')
                    ->sort(999)
                    ->visible(fn () => auth()->user()->email === 'andru.weir@gmail.com'),

            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->url('/platform/my-settings'),
                'change-password' => MenuItem::make()
                    ->label('Change Password')
                    ->icon('heroicon-o-lock-closed')
                    ->url('/platform/change-password'),
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
                //                TwoFactorAuthentication::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                DebuggerPlugin::make(),
            ])
            ->favicon('/favicon.ico')
            ->darkMode(false)
            ->brandName('PRIMA')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications();

        return $panel;
    }
}
