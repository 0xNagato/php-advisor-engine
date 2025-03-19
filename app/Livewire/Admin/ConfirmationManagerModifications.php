<?php

namespace App\Livewire\Admin;

use App\Actions\Booking\SendModificationRequestToVenueContacts;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\BookingModificationRequest;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ConfirmationManagerModifications extends BaseWidget
{
    protected static ?string $heading = '';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BookingModificationRequest::query()
                    ->where('status', BookingModificationRequest::STATUS_PENDING)
                    ->with('booking')
            )->
            recordUrl(fn (BookingModificationRequest $record) => ViewBooking::getUrl(['record' => $record->booking]))
                ->columns([
                    TextColumn::make('id')
                        ->label('Venue')
                        ->formatStateUsing(fn (BookingModificationRequest $record
                        ) => view('partials.booking-confirmation-column',
                            ['record' => $record->booking]))
                        ->searchable(),
                ])
                ->actions([
                    ActionGroup::make([
                        Action::make('resendConfirmation')
                            ->hidden(fn (BookingModificationRequest $record
                            ) => ! auth()->user()->hasActiveRole('super_admin'))
                            ->label('Resend Confirmation')
                            ->requiresConfirmation()
                            ->icon(fn (BookingModificationRequest $record
                            ) => is_null($record->responded_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                            ->color(fn (BookingModificationRequest $record
                            ) => is_null($record->responded_at) ? 'indigo' : 'success')
                            ->requiresConfirmation()
                            ->action(function (BookingModificationRequest $record) {
                                SendModificationRequestToVenueContacts::run($record);
                            }),
                        Action::make('markAsApproved')
                            ->hidden(fn (BookingModificationRequest $record
                            ) => ! auth()->user()->hasActiveRole('super_admin') || ! is_null($record->responded_at))
                            ->label('Mark as Approved')
                            ->requiresConfirmation()
                            ->icon('heroicon-o-check-badge')
                            ->color('success')
                            ->action(function (BookingModificationRequest $record) {
                                $record->markAsApproved();
                                Notification::make()
                                    ->title('Modification Request Approved')
                                    ->success()
                                    ->send();
                            }),
                    ]),
                ]);
    }
}
