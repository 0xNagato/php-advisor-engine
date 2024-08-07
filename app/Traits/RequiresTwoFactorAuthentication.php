<?php

namespace App\Traits;

use App\Filament\Pages\TwoFactorCode;
use App\Services\TwoFactorAuthenticationService;
use Filament\Pages\Page;
use Illuminate\Http\Request;

trait RequiresTwoFactorAuthentication
{
    public function bootRequiresTwoFactorAuthentication(TwoFactorAuthenticationService $twoFactorAuthenticationService, Request $request): void
    {
        abort_unless(is_subclass_of($this, Page::class), 500, 'RequiresTwoFactorAuthentication trait can only be used on FilamentPHP pages.');

        $twoFactorAuthenticationService->registerDevice(auth()->user(), $request);

        if (! $twoFactorAuthenticationService->isDeviceVerified(auth()->user(), $request)) {
            $this->redirect(TwoFactorCode::getUrl([
                'redirect' => request()->fullUrl(),
            ]));
        }
    }
}
