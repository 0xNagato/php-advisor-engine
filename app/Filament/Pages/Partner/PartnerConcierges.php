<?php

namespace App\Filament\Pages\Partner;

use App\Filament\Pages\Concierge\ConciergeReferral;

class PartnerConcierges extends ConciergeReferral
{
    protected static ?string $navigationIcon = 'govicon-user-suit';

    protected static string $view = 'filament.pages.partner.partner-concierge-referrals';

    protected static ?string $title = 'My Concierges';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'partner/concierge';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('partner');
    }
}
