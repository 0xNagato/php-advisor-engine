<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Services\VenueContactBookingConfirmationService;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query = $query
                    ->with('venue')
                    ->orderByDesc('created_at')->where('status', BookingStatus::CONFIRMED);

                if (auth()->user()->hasRole('concierge')) {
                    return $query->where('concierge_id', auth()->user()->concierge->id);
                }

                if (auth()->user()->hasRole('venue')) {
                    return $query->whereHas('schedule.venue', function (Builder $query) {
                        $query->where('id', auth()->user()->venue->id);
                    });
                }

                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(fn (Booking $record) => view('partials.booking-info-column', ['record' => $record])),
                TextColumn::make('total_fee')
                    ->money(fn ($record) => $record->currency, divideBy: 100)
                    ->alignRight(),

            ])
            ->paginated([5, 10])
            ->filters([
                Filter::make('unconfirmed')
                    ->query(fn (Builder $query) => $query->whereNull('venue_confirmed_at')),
            ])
            ->actions([
                Action::make('resendNotification')
                    ->hidden(fn (Booking $record) => ! auth()->user()->hasRole('super_admin'))
                    ->label('Resend Notification')
                    ->requiresConfirmation()
                    ->icon(fn (Booking $record) => is_null($record->venue_confirmed_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                    ->iconButton()
                    ->color(fn (Booking $record) => is_null($record->venue_confirmed_at) ? 'indigo' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        app(VenueContactBookingConfirmationService::class)->sendConfirmation($record);
                        $record->update(['resent_venue_confirmation_at' => now()]);
                    }),
            ]);
    }
}
