<?php

namespace App\Providers;

use App\Models\SpecialRequest;
use App\Models\User;
use App\Policies\SpecialRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SpecialRequest::class => SpecialRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        Gate::define('viewPulse', fn (User $user) => $user->hasRole('super_admin'));
    }
}
