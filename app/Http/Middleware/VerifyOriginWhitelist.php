<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOriginWhitelist
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $referer = (string) $request->headers->get('Referer', '');
        $hostToCheck = $this->extractHostFromHeader($referer);

        if ($hostToCheck === null) {
            return new JsonResponse(['message' => 'Forbidden'], 403);
        }

        $allowed = config('forms.allowed_origins', []);
        if ($this->isHostAllowed($hostToCheck, $allowed)) {
            // Log successful form submission attempt
            logger()->info('Public form submission allowed', [
                'referer' => $referer,
                'host' => $hostToCheck,
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
            ]);

            return $next($request);
        }

        // Log blocked form submission attempt
        logger()->warning('Public form submission blocked', [
            'referer' => $referer,
            'host' => $hostToCheck,
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
        ]);

        return new JsonResponse(['message' => 'Forbidden'], 403);
    }

    private function extractHostFromHeader(?string $header): ?string
    {
        if ($header === null || $header === '') {
            return null;
        }

        // If it's a bare host, parse_url will return false unless prefixed with scheme
        $url = str_contains($header, '://') ? $header : 'https://'.$header;
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower($host);
    }

    /**
     * Check if the given host matches any allowed entry.
     * Supports exact host match and wildcard entries beginning with '*.'
     */
    private function isHostAllowed(string $host, array $allowed): bool
    {
        foreach ($allowed as $entry) {
            $entry = strtolower((string) $entry);

            if ($entry === $host) {
                return true;
            }

            if (str_starts_with($entry, '*.')) {
                $suffix = substr($entry, 1); // remove leading '*'
                if ($suffix !== '' && str_ends_with($host, $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
