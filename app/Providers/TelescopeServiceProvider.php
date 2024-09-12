<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocalOrAllowed = $this->app->environment('local') ||
            in_array(request()->getHost(), ['demo.primavip.co', 'dev.primavip.co']);

        Telescope::filter(fn (IncomingEntry $entry) => $isLocalOrAllowed || $entry->isReportableException() ||
               $entry->isFailedRequest() || $entry->isFailedJob() ||
               $entry->isScheduledTask() || $entry->hasMonitoredTag());
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', fn ($user) => in_array($user->email, [
            'andru.weir@gmail.com',
            'andrew@primavip.co',
        ]));
    }
}
