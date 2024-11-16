<?php

namespace App\Filament\Pages\Admin;

use App\Livewire\Admin\ConfirmationManagerBookings;
use Filament\Pages\Page;

class ConfirmationManager extends Page
{
    protected static ?string $navigationIcon = 'polaris-transaction-icon';

    protected static string $view = 'filament.pages.admin.confirmation-manager';

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConfirmationManagerBookings::make(),
        ];
    }
}
