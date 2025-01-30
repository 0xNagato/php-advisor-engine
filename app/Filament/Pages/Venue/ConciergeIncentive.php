<?php

namespace App\Filament\Pages\Venue;

use App\Constants\BookingPercentages;
use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\RawJs;
use Livewire\Attributes\Computed;

/**
 * @property Form $form
 */
class ConciergeIncentive extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.venue.concierge-incentive';

    protected static ?int $navigationSort = 30;

    public Venue $venue;

    public Region $region;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasActiveRole('venue_manager')) {
            $currentVenue = auth()->user()?->currentVenueGroup()?->currentVenue(auth()->user());

            return $currentVenue?->name ?? 'Venue Management';
        }

        return null;
    }

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasActiveRole('venue')) {
            return true;
        }

        if ($user->hasActiveRole('venue_manager')) {
            $venueGroup = $user->currentVenueGroup();

            return filled($venueGroup?->getAllowedVenueIds($user));
        }

        return false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole(['venue', 'venue_manager']), 403);

        if (auth()->user()->hasActiveRole('venue')) {
            $this->venue = auth()->user()->venue;
        } elseif (auth()->user()->hasActiveRole('venue_manager')) {
            $venueGroup = auth()->user()->currentVenueGroup();
            $currentVenue = $venueGroup?->currentVenue(auth()->user());

            abort_unless((bool) $currentVenue, 404, 'No active venue selected');
            $this->venue = $currentVenue;
        } else {
            abort(403, 'You are not authorized to access this page');
        }

        $this->region = $this->venue->inRegion;

        $this->form->fill([
            'non_prime_type' => $this->venue->non_prime_type,
            'non_prime_fee_per_head' => $this->venue->non_prime_fee_per_head,
        ]);
    }

    #[Computed]
    public function conciergeIncentivePercentage(): int
    {
        return BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Radio::make('non_prime_type')
                ->label('Would you like to offer an incentive to the PRIMA Concierge Network to book non-prime reservations?')
                ->options([
                    'paid' => 'Yes',
                    'free' => 'No',
                ])
                ->inline()
                ->default('paid')
                ->live()
                ->required(),
            TextInput::make('non_prime_fee_per_head')
                ->label('Non-Prime Bounty (per customer)')
                ->helperText('Enter the additional fee per person for non-prime hours. This fee will be charged for each guest during non-prime hours.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->minValue(5)
                ->prefix(fn () => $this->region->currency_symbol)
                ->hidden(fn (Get $get) => $get('non_prime_type') === 'free')
                ->required(),
        ])
            ->statePath('data')
            ->model($this->venue);
    }

    public function saveForm(): void
    {
        $data = $this->form->getState();
        $this->venue->update([
            'non_prime_type' => $data['non_prime_type'],
            'non_prime_fee_per_head' => $data['non_prime_fee_per_head'] ?? $this->venue->non_prime_fee_per_head,
        ]);

        Notification::make()
            ->title('Non-prime fees updated successfully.')
            ->success()
            ->send();
    }
}
