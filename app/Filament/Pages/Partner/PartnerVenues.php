<?php

namespace App\Filament\Pages\Partner;

use App\Filament\Resources\VenueResource\Pages\CreateVenue;
use App\Livewire\Partner\VenueReferralsTable;
use Filament\Actions\Action;
use Filament\Pages\Page;

class PartnerVenues extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.partner.partner-venue-referrals';

    protected static ?string $title = 'My Venues';

    protected static ?string $slug = 'partner/venue';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('partner');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VenueReferralsTable::make(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Create Venue')
                ->label('Create Venue')
                ->icon('heroicon-o-plus-circle')
                ->url(CreateVenue::getUrl()),
        ];
    }
}
