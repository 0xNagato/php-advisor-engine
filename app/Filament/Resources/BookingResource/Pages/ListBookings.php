<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query = $query->orderByDesc('created_at')->where('status', BookingStatus::CONFIRMED);

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
                Tables\Columns\TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->numeric()
                    ->sortable()
                    ->hidden(function () {
                        return auth()->user()->hasRole('concierge');
                    }),
                Tables\Columns\TextColumn::make('schedule.restaurant.restaurant_name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable()
                    ->hidden(function () {
                        return auth()->user()->hasRole('restaurant');
                    }),
                Tables\Columns\TextColumn::make('booking_at')
                    ->label('When')
                    ->dateTime('D, M j')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_fee')
                    ->currency('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
