<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\ConciergeInvitationForms;
use App\Traits\HandlesBulkInviteConciergeInvitations;
use Filament\Pages\Page;

class PartnerConcierges extends Page
{
    use HandlesBulkInviteConciergeInvitations;

    protected static ?string $navigationIcon = 'govicon-user-suit';

    protected static string $view = 'filament.pages.partner.partner-concierge-referrals';

    protected static ?string $title = 'My Concierges';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'partner/concierge';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('partner');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConciergeInvitationForms::make(),
        ];
    }
}
