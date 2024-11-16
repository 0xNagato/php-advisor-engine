<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\EarningType;
use App\Filament\Resources\PaymentResource;
use App\Models\Earning;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    public static bool $canCreateAnother = false;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    protected function beforeCreate(): void
    {
        if (! $this->hasEarnings()) {
            Notification::make()
                ->warning()
                ->title('No Earnings Found')
                ->body('No earnings found for the selected month, type and currency.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = EarningType::from($data['type'])->getLabel();

        $title = 'Payment for '.$type.' in '.$data['currency'];

        $data['title'] = $title;
        $data['amount'] = $data['amount'] * 100;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Payment $payment */
        $payment = $this->record;
        $data = $this->data;

        $earnings = Earning::query()
            ->with('user')
            ->whereNull('payment_id')
            ->where('type', $data['type'])
            ->where('currency', $data['currency'])
            ->whereMonth('created_at', (int) $data['month']);

        $paymentItems = $earnings
            ->clone()
            ->get()
            ->groupBy('user_id')
            ->map(function ($earnings) use ($payment) {
                $payment->items()->create([
                    'user_id' => $earnings->first()->user_id,
                    'currency' => $earnings->first()->currency,
                    'amount' => $earnings->sum('amount'),
                ]);
            });

        $updatePayments = $earnings->clone()->each(
            fn ($earning) => $earning->update(['payment_id' => $payment->id])
        );

        Notification::make()
            ->title('Payment Created');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->disabled(
                fn (): bool => ! $this->hasEarnings()
            );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    public function hasEarnings(): bool
    {
        $earnings = Earning::query()
            ->with('user')
            ->whereNull('payment_id')
            ->where('type', $this->data['type'])
            ->where('currency', $this->data['currency'])
            ->whereMonth('created_at', (int) $this->data['month'])
            ->count();

        return $earnings > 0;
    }
}
