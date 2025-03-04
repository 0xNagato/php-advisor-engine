<?php

namespace App\Filament\Pages\Admin;

use App\Models\User;
use Filament\Pages\Page;

class SpecialRequests extends Page
{
    protected static ?string $navigationIcon = 'polaris-bill-filled-icon';

    protected static string $view = 'filament.pages.concierge.special-request-form';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'admin/special-requests';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->hasActiveRole('super_admin');
    }
}
