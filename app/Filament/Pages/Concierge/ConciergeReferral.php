<?php

namespace App\Filament\Pages\Concierge;

use App\Livewire\Partner\ConciergeInvitationForms;
use App\Traits\HandlesBulkInviteConciergeInvitations;
use Filament\Pages\Page;

class ConciergeReferral extends Page
{
    use HandlesBulkInviteConciergeInvitations;

    protected static ?string $navigationIcon = 'gmdi-people-alt-tt';

    protected static string $view = 'filament.pages.concierge.concierge-referral';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'My Referrals';

    public static function canAccess(): bool
    {
        if (session()->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()->hasActiveRole('concierge');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConciergeInvitationForms::make(),
        ];
    }
}
