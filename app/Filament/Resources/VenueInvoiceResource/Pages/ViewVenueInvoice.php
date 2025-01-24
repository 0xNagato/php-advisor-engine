<?php

declare(strict_types=1);

namespace App\Filament\Resources\VenueInvoiceResource\Pages;

use App\Enums\VenueInvoiceStatus;
use App\Filament\Resources\VenueInvoiceResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewVenueInvoice extends ViewRecord
{
    protected static string $resource = VenueInvoiceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Invoice Details')
                            ->columnSpan(2)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('invoice_number')
                                            ->label('Invoice Number'),
                                        TextEntry::make('venue.name')
                                            ->label('Venue'),
                                        TextEntry::make('date_range')
                                            ->label('Date Range')
                                            ->state(fn ($record): string => "{$record->start_date->format('M j, Y')} - {$record->end_date->format('M j, Y')}"),
                                        TextEntry::make('total_amount')
                                            ->label('Total Amount')
                                            ->money(fn ($record) => $record->currency, 100),
                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (VenueInvoiceStatus $state): string => match ($state) {
                                                VenueInvoiceStatus::DRAFT => 'gray',
                                                VenueInvoiceStatus::SENT => 'info',
                                                VenueInvoiceStatus::PAID => 'success',
                                                VenueInvoiceStatus::VOID => 'danger',
                                            }),
                                        TextEntry::make('due_date')
                                            ->label('Due Date')
                                            ->date(),
                                    ]),
                            ]),
                        Section::make('Timeline')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)
                                        ->timezone(auth()->user()->timezone ?? config('app.timezone'))
                                        ->format('M j, Y g:ia')),
                                TextEntry::make('sent_at')
                                    ->label('Sent')
                                    ->placeholder('--')
                                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)
                                        ->timezone(auth()->user()->timezone ?? config('app.timezone'))
                                        ->format('M j, Y g:ia') : '--'),
                                TextEntry::make('paid_at')
                                    ->label('Paid')
                                    ->placeholder('--')
                                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)
                                        ->timezone(auth()->user()->timezone ?? config('app.timezone'))
                                        ->format('M j, Y g:ia') : '--'),
                            ]),
                    ]),
            ]);
    }

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
