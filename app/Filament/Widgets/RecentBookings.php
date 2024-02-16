<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentBookings extends BaseWidget
{

    protected string|int|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Booking::query();

        // check if user is concierge and get all bookings for their hotel
        if (auth()->user()?->hasRole('concierge')) {
            $query = Booking::where('concierge_id', auth()->user()->concierge->id);
        }

        // check if user is restaurant manager and get all bookings for their restaurant
        if (auth()->user()?->hasRole('restaurant-manager')) {
            $query = Booking::where('schedule.restaurant_id', auth()->user()->restaurant->id);
        }


        $query = $query->whereDate('created_at', '>=', Carbon::now()->subDays(30));


        return $table
            ->query(
                $query
            )
            ->columns([
                TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->numeric()
                    ->hidden((bool)auth()->user()?->hasRole('concierge'))
                    ->sortable(),
                TextColumn::make('schedule.restaurant.restaurant_name')
                    ->label('Restaurant')
                    ->hidden((bool)auth()->user()?->hasRole('restaurant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.start_time')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('guest_name')
                    ->searchable(),
                TextColumn::make('guest_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_fee')
                    ->currency('USD')
                    ->sortable(),
            ]);
    }
}
