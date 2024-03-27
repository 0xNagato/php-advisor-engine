<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentInformation extends Page
{
    protected static ?string $navigationIcon = 'gmdi-payment-o';

    protected static string $view = 'filament.pages.payment-information';

    protected static ?int $navigationSort = 20;

    public string $payout_type;

    public string $payout_name;

    public string $routing_number;

    public string $account_number;

    public string $account_type;

    public array $payoutOptions = [
        'ACH',
        'PayPal',
        'Venmo',
    ];

    public int $charity_percentage;

    public User $user;

    public string $address_1;

    public string $address_2;

    public string $city;

    public string $state;

    public string $zip;

    public string $country;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('concierge') || auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->user = User::find(auth()->id());

        $this->payout_type = $this->user->payout?->payout_type ?? '';
        $this->payout_name = $this->user->payout?->payout_name ?? '';
        $this->routing_number = $this->user->payout?->routing_number ?? '';
        $this->account_number = $this->user->payout?->account_number ?? '';
        $this->account_type = $this->user->payout?->account_type ?? '';
        $this->charity_percentage = $this->user->charity_percentage ?? 5;

        $this->address_1 = $this->user->address_1 ?? '';
        $this->address_2 = $this->user->address_2 ?? '';
        $this->city = $this->user->city ?? '';
        $this->state = $this->user->state ?? '';
        $this->zip = $this->user->zip ?? '';
        $this->country = $this->user->country ?? '';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('address_1')
                ->label('Address Line 1')
                ->required(),
            TextInput::make('address_2')
                ->label('Address Line 2'),
            TextInput::make('city')
                ->label('City')
                ->required(),
            TextInput::make('state')
                ->label('State')
                ->required(),
            TextInput::make('zip')
                ->label('Zip')
                ->required(),
            TextInput::make('country')
                ->label('Country')
                ->required(),
        ]);
    }

    public function updatedPayoutType($value): void
    {
        $this->payout_name = '';
        // $this->routing_number = '';
        // $this->account_number = '';
        // $this->account_type = '';
    }

    public function save(): void
    {
        $this->user->update([
            'payout' => [
                'payout_type' => $this->payout_type,
                'payout_name' => $this->payout_name,
                'routing_number' => $this->routing_number,
                'account_number' => $this->account_number,
                'account_type' => $this->account_type,
            ],
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
        ]);

        Notification::make()
            ->title('Payout information updated successfully.')
            ->success()
            ->send();
    }
}
