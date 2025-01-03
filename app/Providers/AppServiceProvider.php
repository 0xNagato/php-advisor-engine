<?php

/** @noinspection UnknownInspectionInspection */

/** @noinspection JSUnresolvedReference, JSUnresolvedLibraryURL */

namespace App\Providers;

use App\Models\User;
use App\Observers\UserManyChatObserver;
use App\Services\Booking\BookingCalculationService;
use App\Services\Booking\EarningCreationService;
use App\Services\Booking\NonPrimeEarningsCalculationService;
use App\Services\Booking\PrimeEarningsCalculationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Lorisleiva\Actions\Facades\Actions;
use Opcodes\LogViewer\Facades\LogViewer;
use Stripe\StripeClient;

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

        Actions::registerCommands();

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

        Carbon::macro('toNotificationFormat', static function (Carbon $date): string {
            $today = now();
            $tomorrow = now()->addDay();

            if ($date->isSameDay($today)) {
                return 'today';
            }

            if ($date->isSameDay($tomorrow)) {
                return 'tomorrow';
            }

            return $date->format('D M jS');
        });

        Carbon::macro('inAppTimezone', fn (): Carbon => $this->tz(config('app.default_timezone')));
        Carbon::macro('inUserTimezone',
            fn (): Carbon => $this->tz(auth()->user()?->timezone ?? config('app.default_timezone')));

        Blade::directive('mobileapp', static fn () => '<?php if(isPrimaApp()): ?>');

        Blade::directive('endmobileapp', static fn () => '<?php endif; ?>');

        Blade::directive('nonmobileapp', static fn () => '<?php if(!isPrimaApp()): ?>');

        Blade::directive('endnonmobileapp', static fn () => '<?php endif; ?>');

        Blade::directive('hasActiveRole', fn ($roles) => "<?php if(auth()->user()?->hasActiveRole($roles)): ?>");

        Blade::directive('endhasActiveRole', fn () => '<?php endif; ?>');

        $this->app->bind(EarningCreationService::class, fn () => new EarningCreationService);

        $this->app->bind(PrimeEarningsCalculationService::class, fn ($app) => new PrimeEarningsCalculationService(
            $app->make(EarningCreationService::class)
        ));

        $this->app->bind(NonPrimeEarningsCalculationService::class, fn ($app) => new NonPrimeEarningsCalculationService(
            $app->make(EarningCreationService::class)
        ));

        $this->app->bind(BookingCalculationService::class, fn ($app) => new BookingCalculationService(
            $app->make(PrimeEarningsCalculationService::class),
            $app->make(NonPrimeEarningsCalculationService::class)
        ));

        JsonResource::withoutWrapping();
        Model::preventLazyLoading(! $this->app->isProduction());
        User::observe(UserManyChatObserver::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') ||
            ($this->app->environment('production') && in_array(request()->getHost(),
                ['demo.primavip.co', 'dev.primavip.co']))) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(TelescopeApplicationServiceProvider::class);
        }

        $this->app->singleton(StripeClient::class, fn () => new StripeClient(config('services.stripe.secret')));
    }
}
