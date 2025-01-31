<?php

namespace App\Livewire\Admin;

use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ConfirmationManagerBookings extends BaseWidget
{
    protected static ?string $heading = '';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->confirmed()
                    ->orderByRaw('venue_confirmed_at IS NOT NULL')
                    ->orderBy('created_at', 'desc')
            )
            ->recordUrl(fn (Booking $record) => ViewBooking::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->formatStateUsing(fn (Booking $record) => view('partials.booking-confirmation-column', ['record' => $record]))
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('resendConfirmation')
                        ->hidden(fn (Booking $record) => ! auth()->user()->hasActiveRole('super_admin'))
                        ->label('Resend Confirmation')
                        ->requiresConfirmation()
                        ->icon(fn (Booking $record) => is_null($record->venue_confirmed_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                        ->color(fn (Booking $record) => is_null($record->venue_confirmed_at) ? 'indigo' : 'success')
                        ->requiresConfirmation()
                        ->hidden(fn (Booking $record) => ! is_null($record->venue_confirmed_at))
                        ->action(function (Booking $record) {
                            SendConfirmationToVenueContacts::run($record);
                            $record->update(['resent_venue_confirmation_at' => now()]);
                        }),
                ]),
            ]);
    }
}
