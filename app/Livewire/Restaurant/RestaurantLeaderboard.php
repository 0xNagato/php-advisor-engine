<?php

namespace App\Livewire\Restaurant;

use App\Models\Booking;
use App\Models\Restaurant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RestaurantLeaderboard extends BaseWidget
{
    public ?Restaurant $restaurant;

    public bool $showFilters = false;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::select('restaurants.user_id', DB::raw('sum(restaurant_earnings) as total_earned'), 'restaurants.restaurant_name')
            ->join('schedules', 'schedules.id', '=', 'bookings.schedule_id')
            ->join('restaurants', 'restaurants.id', '=', 'schedules.restaurant_id')
            ->join('users', 'users.id', '=', 'restaurants.user_id')
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->groupBy('restaurants.user_id', 'restaurants.restaurant_name')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->columns(components: [
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('restaurant_name')
                    ->label('Restaurant Name')
                    ->formatStateUsing(function ($state, $record) {
                        // current user is restaurant display their name if not display the name of the ******* else display names
                        if ($this->showFilters) {
                            if (auth()->user()->restaurant->user_id === $record->user_id) {
                                return 'You';
                            }

                            return '*********';
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->currency('USD'),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'restaurants.user_id';
    }
}
