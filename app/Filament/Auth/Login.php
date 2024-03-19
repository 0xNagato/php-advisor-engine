<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Login as BaseAuth;

class Login extends BaseAuth
{
    protected static string $view = 'filament.pages.auth.login';

    protected static string $layout = 'components.layouts.app';

    public function getSubheading(): string
    {
        return __('Custom Page Heading');
    }
}
