<?php

namespace App\Filament\Pages\Profile;

use App\Livewire\Profile\PasswordSettings;
use App\Traits\RequiresTwoFactorAuthentication;
use Filament\Pages\Page;

class ChangePassword extends Page
{
    use RequiresTwoFactorAuthentication;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.pages.change-password';

    protected static ?int $navigationSort = 100;

    protected static bool $shouldRegisterNavigation = false;

    public function getHeaderWidgets(): array
    {
        return [
            PasswordSettings::make(),
        ];
    }
}
