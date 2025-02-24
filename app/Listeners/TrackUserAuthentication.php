<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class TrackUserAuthentication
{
    public function handle(Login $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        // Check if we've already logged this authentication event
        if (session()->get('auth_logged') === $user->id . '_' . request()->ip()) {
            return;
        }

        // Update user's last login information
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip()
        ]);

        Log::info('User authenticated successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Mark this authentication as logged
        session()->put('auth_logged', $user->id . '_' . request()->ip());
    }
} 