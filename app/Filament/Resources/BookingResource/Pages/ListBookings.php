<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(function (Booking $record) {
                        return view('partials.booking-info-column', ['record' => $record]);
                    }),

                // TextColumn::make('concierge.user.name')
                //     ->label('Concierge')
                //     ->numeric()
                //     ->sortable()
                //     ->hidden(function () {
                //         return auth()->user()->hasRole('concierge');
                //     }),
                // TextColumn::make('schedule.restaurant.restaurant_name')
                //     ->label('Restaurant')
                //     ->searchable()
                //     ->sortable()
                //     ->hidden(function () {
                //         return auth()->user()->hasRole('restaurant');
                //     }),
                TextColumn::make('total_fee')
                    ->money('USD', divideBy: 100)
                    ->alignRight()
                    ->sortable(),
            ]);
    }
}
