<?php

namespace App\Filament\Pages\Profile;

use App\Models\User;
use App\Traits\RequiresTwoFactorAuthentication;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\RedirectResponse;

class PaymentInformation extends Page
{
    use RequiresTwoFactorAuthentication;

    protected static ?string $navigationIcon = 'gmdi-payment-o';

    protected static string $view = 'filament.pages.payment-information';

    protected static ?string $navigationGroup = 'Financials';

    protected static ?int $navigationSort = 100;

    public string $payout_type;

    public string $payout_name;

    public string $routing_number;

    public string $account_number;

    public string $account_type;

    public array $payoutOptions = [
        'Direct Deposit',
        'Check',
        'IBAN',
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
        if (session()?->exists('simpleMode')) {
            return !session('simpleMode');
        }

        $user = auth()->user();

        // Allow access for concierge, partner roles
        if ($user->hasActiveRole(['concierge', 'partner', 'venue'])) {
            return true;
        }

        // For venue managers, only allow access if they are the primary manager
        if ($user->hasActiveRole('venue_manager')) {
            $currentVenueGroup = $user->currentVenueGroup();

            return $currentVenueGroup && $currentVenueGroup->primary_manager_id === $user->id;
        }

        return false;
    }

    public function mount(): void
    {
        if (auth()->user()->hasActiveRole('concierge')) {
            $this->payoutOptions = [
                'Direct Deposit',
                'IBAN',
            ];
        }

        $this->user = User::query()->find(auth()->id());

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
        return $form
            ->schema([
                TextInput::make('address_1')
                    ->placeholder('Address Line 1')
                    ->required()
                    ->columnSpan(2)
                    ->hiddenLabel(),
                TextInput::make('address_2')
                    ->placeholder('Address Line 2')
                    ->columnSpan(2)
                    ->hiddenLabel(),
                TextInput::make('city')
                    ->placeholder('City')
                    ->required()
                    ->columnSpan(1)
                    ->hiddenLabel(),
                TextInput::make('state')
                    ->placeholder('State')
                    ->required()
                    ->columnSpan(1)
                    ->hiddenLabel(),
                TextInput::make('zip')
                    ->placeholder('Zip Code')
                    ->required()
                    ->hiddenLabel(),
                TextInput::make('country')
                    ->placeholder('Country')
                    ->required()
                    ->hiddenLabel(),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ]);
    }

    public function updatedPayoutType(): void
    {
        $this->payout_name = '';
        // $this->routing_number = '';
        // $this->account_number = '';
        // $this->account_type = '';
    }

    public function save(): ?RedirectResponse
    {
        $pendingData = [
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
        ];

        $this->user->update($pendingData);

        Notification::make()
            ->title('Payout information updated successfully.')
            ->success()
            ->send();

        return null;
    }
}
