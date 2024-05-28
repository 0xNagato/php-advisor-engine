<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'paid' => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', PaymentStatus::PAID)),
            'pending' => Tab::make()
                ->badge(
                    Payment::query()
                        ->where('status', PaymentStatus::PENDING)
                        ->when(
                            ! auth()->user()->hasRole('super_admin'),
                            fn ($query) => $query->where('user_id', auth()->id())
                        )
                        ->count()
                )
                ->modifyQueryUsing(
                    fn ($query) => $query->where('status', PaymentStatus::PENDING)
                ),
            'failed' => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', PaymentStatus::FAILED)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
