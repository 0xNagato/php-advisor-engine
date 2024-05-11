<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Services\RestaurantContactBookingConfirmationService;
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
                    ->with('restaurant')
                    ->orderByDesc('created_at')->where('status', BookingStatus::CONFIRMED);

                if (auth()->user()->hasRole('concierge')) {
                    return $query->where('concierge_id', auth()->user()->concierge->id);
                }

                if (auth()->user()->hasRole('restaurant')) {
                    return $query->whereHas('schedule.restaurant', function (Builder $query) {
                        $query->where('id', auth()->user()->restaurant->id);
                    });
                }

                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(function (Booking $record) {
                        return view('partials.booking-info-column', ['record' => $record]);
                    }),
                TextColumn::make('total_fee')
                    ->money(fn ($record) => $record->currency, divideBy: 100)
                    ->alignRight(),

            ])
            ->filters([
                Filter::make('unconfirmed')
                    ->query(fn (Builder $query) => $query->whereNull('restaurant_confirmed_at')),
            ])
            ->actions([
                Action::make('resendNotification')
                    ->hidden(fn (Booking $record) => ! auth()->user()->hasRole('super_admin'))
                    ->label('Resend Notification')
                    ->requiresConfirmation()
                    ->icon(fn (Booking $record) => is_null($record->restaurant_confirmed_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                    ->iconButton()
                    ->color(fn (Booking $record) => is_null($record->restaurant_confirmed_at) ? 'indigo' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        app(RestaurantContactBookingConfirmationService::class)->sendConfirmation($record);
                        $record->update(['resent_restaurant_confirmation_at' => now()]);
                    }),
            ]);
    }
}
