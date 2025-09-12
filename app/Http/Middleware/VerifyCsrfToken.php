<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/platform/app/login',
        '/platform/app/logout',
        '/privacy',
        '/about-us',
        '/contact',
        '/consumers',
        '/restaurants',
        '/concierges',
        '/story',
        '/',
        // Allow heartbeat pings without CSRF to keep sessions alive
        '/heartbeat',
    ];

    /**
     * Livewire components that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $excludedComponents = [
        'talk-to-prima',
        'site-contact-form',
    ];

    /**
     * Get Livewire component path from the request.
     */
    protected function getLivewireComponentPath(mixed $request): ?string
    {
        $components = $request->input('components')[0] ?? [];
        $snapshot = json_decode($components['snapshot'] ?? '{}', true);
        $memo = $snapshot['memo'] ?? [];

        return $memo['name'] ?? null;
    }

    /**
     * Check if the CSRF tokens match for the given request.
     */
    protected function tokensMatch($request): bool
    {
        $componentPath = $this->getLivewireComponentPath($request);

        if (in_array($componentPath, $this->excludedComponents, true)) {
            return true;
        }

        return parent::tokensMatch($request);
    }
}
