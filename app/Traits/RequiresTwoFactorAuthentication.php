<?php

namespace App\Traits;

use App\Filament\Pages\TwoFactorCode;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Filament\Pages\Page;
use Illuminate\Http\Request;

trait RequiresTwoFactorAuthentication
{
    public function bootRequiresTwoFactorAuthentication(TwoFactorAuthenticationService $twoFactorAuthenticationService, Request $request): void
    {
        abort_unless(is_subclass_of($this, Page::class), 500, 'RequiresTwoFactorAuthentication trait can only be used on FilamentPHP pages.');

        /**
         * If a super_admin is impersonating another user, they are not required to use two-factor authentication.
         */
        if (app('impersonate')->isImpersonating()) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        $twoFactorAuthenticationService->registerDevice($user, $request);

        if (! $twoFactorAuthenticationService->isDeviceVerified($user, $request)) {
            $this->redirect(TwoFactorCode::getUrl([
                'redirect' => request()->fullUrl(),
            ]));
        }
    }
}
