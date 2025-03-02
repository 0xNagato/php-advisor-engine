<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\VenueReferralsTable;
use App\Services\PrimaShortUrls;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
        return auth()->user()?->hasActiveRole('partner');
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
            Action::make('shareOnboardingLink')
                ->label('Share Onboarding Link')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->modalHeading('Venue Onboarding Link')
                ->modalDescription('Share this link with venues to start their onboarding process. The link is pre-filled with your Partner ID.')
                ->form([
                    TextInput::make('onboarding_link')
                        ->label('Onboarding Link')
                        ->default(function () {
                            $partnerId = auth()->id();

                            return PrimaShortUrls::getPartnerOnboardingUrl($partnerId);
                        })
                        ->readOnly()
                        ->suffixAction(
                            \Filament\Forms\Components\Actions\Action::make('copy')
                                ->icon('heroicon-m-clipboard')
                                ->tooltip('Copy to clipboard')
                                ->action(function (TextInput $component) {
                                    // Native browser clipboard API
                                    $this->js('navigator.clipboard.writeText("'.$component->getState().'")');

                                    // Show a notification
                                    Notification::make()
                                        ->title('Copied to clipboard')
                                        ->success()
                                        ->send();
                                })
                        )
                        ->helperText('Copy this link and share it with venues. When they complete the onboarding process, they will automatically be added to your venues.'),
                ])
                ->modalSubmitActionLabel('Close')
                ->modalCancelAction(false),
        ];
    }
}
