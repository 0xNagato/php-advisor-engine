<?php

namespace App\Filament\Auth;

class RequestPasswordReset extends \Filament\Pages\Auth\PasswordReset\RequestPasswordReset
{
    protected static string $layout = 'components.layouts.app';
    protected static string $view = 'filament.pages.auth.request-password-reset';
}
