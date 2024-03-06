<?php

namespace App\Filament\Pages;

use App\Models\User;
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

    public User $user;

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
        ]);

        Notification::make()
            ->title('Payout information updated successfully.')
            ->success()
            ->send();
    }
}
