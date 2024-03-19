<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends \Filament\Pages\Auth\PasswordReset\ResetPassword
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'filament.pages.auth.reset-password';

    public function getTitle(): string|Htmlable
    {
        return 'Secure Your Account';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Secure Your Account';
    }

    public function getResetPasswordFormAction(): Action
    {
        return Action::make('resetPassword')
            ->label('Secure Your Account')
            ->submit('resetPassword');
    }
}
