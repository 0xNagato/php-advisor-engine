<?php

namespace App\Filament\Pages;

use App\Livewire\PasswordSettings;
use Filament\Pages\Page;

class ChangePassword extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.pages.change-password';

    protected static ?int $navigationSort = 100;

    public function getHeaderWidgets(): array
    {
        return [
            PasswordSettings::make(),
        ];
    }
}
