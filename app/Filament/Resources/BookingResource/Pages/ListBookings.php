<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Exception;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query = $query
                    ->with('venue')
                    ->orderByDesc('created_at')
                    ->whereIn('status', [
                        BookingStatus::CONFIRMED,
                        BookingStatus::VENUE_CONFIRMED,
                        BookingStatus::REFUNDED,
                        BookingStatus::PARTIALLY_REFUNDED,
                    ]);

                if (auth()->user()->hasActiveRole('concierge')) {
                    return $query->where('concierge_id', auth()->user()->concierge->id);
                }

                if (auth()->user()->hasActiveRole('venue')) {
                    return $query->whereHas('schedule.venue', function (Builder $query) {
                        $query->where('id', auth()->user()->venue->id);
                    });
                }

                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(fn (Booking $record) => view(
                        'partials.booking-info-column',
                        ['record' => $record]
                    )),
                TextColumn::make('total_fee')
                    ->size('xs')
                    ->money(fn ($record) => $record->currency, divideBy: 100)
                    ->alignRight(),
            ])
            ->paginated([10, 25, 50, 100])
            ->filters([
                Filter::make('confirmed')
                    ->query(fn (Builder $query) => $query->whereNotNull('confirmed_at')),
                Filter::make('unconfirmed')
                    ->query(fn (Builder $query) => $query->whereNull('venue_confirmed_at')),
                Filter::make('vip_code_id')
                    ->label('VIP')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('vip_code_id')),
                Filter::make('refunded')
                    ->label('Refunded')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [
                        BookingStatus::REFUNDED,
                        BookingStatus::PARTIALLY_REFUNDED,
                    ])),
                SelectFilter::make('is_prime')
                    ->options([0 => 'Non Prime', 1 => 'Prime']),
            ])
            ->actions([
                Action::make('resendNotification')
                    ->hidden(fn () => ! auth()->user()->hasActiveRole('super_admin'))
                    ->label('Resend Notification')
                    ->requiresConfirmation()
                    ->icon(fn (Booking $record
                    ) => is_null($record->venue_confirmed_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                    ->iconButton()
                    ->color(fn (Booking $record) => is_null($record->venue_confirmed_at) ? 'indigo' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        SendConfirmationToVenueContacts::run($record);
                        $record->update(['resent_venue_confirmation_at' => now()]);
                    }),
            ]);
    }
}
