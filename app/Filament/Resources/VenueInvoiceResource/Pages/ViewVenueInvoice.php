<?php

declare(strict_types=1);

namespace App\Filament\Resources\VenueInvoiceResource\Pages;

use App\Enums\VenueInvoiceStatus;
use App\Filament\Resources\VenueInvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewVenueInvoice extends ViewRecord
{
    protected static string $resource = VenueInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->icon('heroicon-m-arrow-down-on-square')
                ->action(fn () => Storage::disk('do')->download($this->record->pdf_path, $this->record->name().'.pdf')),

            Action::make('mark_as_sent')
                ->icon('heroicon-m-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === VenueInvoiceStatus::DRAFT)
                ->action(function () {
                    $this->record->update([
                        'status' => VenueInvoiceStatus::SENT,
                        'sent_at' => now(),
                    ]);

                    activity()
                        ->performedOn($this->record)
                        ->event('marked_as_sent')
                        ->log('Marked invoice as sent');
                }),

            Action::make('mark_as_paid')
                ->icon('heroicon-m-banknotes')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === VenueInvoiceStatus::SENT)
                ->action(function () {
                    $this->record->update([
                        'status' => VenueInvoiceStatus::PAID,
                        'paid_at' => now(),
                    ]);

                    activity()
                        ->performedOn($this->record)
                        ->event('marked_as_paid')
                        ->log('Marked invoice as paid');
                }),

            Action::make('void')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to void this invoice? This action cannot be undone.')
                ->visible(fn () => in_array($this->record->status, [VenueInvoiceStatus::DRAFT, VenueInvoiceStatus::SENT]))
                ->action(function () {
                    $this->record->update([
                        'status' => VenueInvoiceStatus::VOID,
                    ]);

                    activity()
                        ->performedOn($this->record)
                        ->event('voided')
                        ->log('Voided invoice');
                }),
        ];
    }
}
