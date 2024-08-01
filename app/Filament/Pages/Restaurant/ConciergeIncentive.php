<?php

namespace App\Filament\Pages\Restaurant;

use App\Constants\BookingPercentages;
use App\Models\Region;
use App\Models\Restaurant;
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

    protected static string $view = 'filament.pages.restaurant.concierge-incentive';

    protected static ?int $navigationSort = 30;

    public Restaurant $restaurant;

    public Region $region;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->restaurant = auth()->user()->restaurant ?? abort(403);
        $this->region = $this->restaurant->inRegion;

        $this->form->fill([
            'non_prime_type' => $this->restaurant->non_prime_type,
            'non_prime_fee_per_head' => $this->restaurant->non_prime_fee_per_head,
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
            ->model($this->restaurant);
    }

    public function saveForm(): void
    {
        $data = $this->form->getState();
        $this->restaurant->update([
            'non_prime_type' => $data['non_prime_type'],
            'non_prime_fee_per_head' => $data['non_prime_fee_per_head'] ?? $this->restaurant->non_prime_fee_per_head,
        ]);

        Notification::make()
            ->title('Non-prime fees updated successfully.')
            ->success()
            ->send();
    }
}
