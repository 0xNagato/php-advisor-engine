<?php

/** @noinspection JSUnresolvedReference, JSUnresolvedLibraryURL */

namespace App\Providers;

use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
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

        LogViewer::auth(static fn ($request) => $request->user()
            && $request->user()->hasRole('super_admin'));

        FilamentColor::register([
            'primary' => Color::Indigo,
            'indigo' => Color::Indigo,
            'brand' => '#4736dd',
            'gold' => '#DAA520',
            'silver' => '#A9A9A9',
        ]);

        Page::$reportValidationErrorUsing = static function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };

        Carbon::macro('toNotificationFormat', function (Carbon $date): string {
            $today = now();
            $tomorrow = now()->addDay();

            if ($date->isSameDay($today)) {
                return 'today';
            }

            if ($date->isSameDay($tomorrow)) {
                return 'tomorrow';
            }

            return $date->format('l \\t\\h\\e jS');
        });

        Model::preventLazyLoading(! $this->app->isProduction());
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
