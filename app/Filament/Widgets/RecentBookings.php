<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 2;
    protected string|int|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Booking::orderByDesc('booking_at');

        // check if user is concierge and get all bookings for their hotel
        if (auth()->user()?->hasRole('concierge')) {
            $query = Booking::where('concierge_id', auth()->user()->concierge->id);
        }

        // check if user is restaurant manager and get all bookings for their restaurant
        if (auth()->user()?->hasRole('restaurant')) {
            $query = Booking::whereHas('schedule', function ($query) {
                $query->where('restaurant_id', auth()->user()->restaurant->id);
            });
        }


        // Get startDate and endDate from page filters
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        // Use startDate and endDate in the query
        $query = $query->whereBetween('created_at', [$startDate, $endDate]);


        return $table
            ->query($query)
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
                TextColumn::make('booking_at')
                    ->label('When')
                    ->dateTime('D, M j')
                    ->sortable(),
                TextColumn::make('guest_name')
                    ->toggleable(isToggledHiddenByDefault: true)
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
