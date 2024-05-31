<?php

namespace App\Filament\Pages\Concierge;

use App\Models\User;
use Filament\Pages\Page;

class SpecialRequests extends Page
{
    protected static ?string $navigationIcon = 'polaris-bill-filled-icon';

    protected static string $view = 'filament.pages.concierge.special-request-form';

    protected static ?int $navigationSort = -2;

    protected static ?string $slug = 'concierge/special-requests';

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->hasRole('concierge');
    }
}
