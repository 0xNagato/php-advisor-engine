<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RestaurantLeaderboard extends BaseWidget
{
    protected static ?int $sort = 2;


    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::select('schedules.restaurant_id', DB::raw('sum(total_fee * bookings.payout_restaurant / 100) as total_earned'), 'restaurants.restaurant_name')
            ->join('schedules', 'schedules.id', '=', 'bookings.schedule_id')
            ->join('restaurants', 'restaurants.id', '=', 'schedules.restaurant_id')
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->groupBy('schedules.restaurant_id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('restaurant_name')
                    ->label('Restaurant Name'),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->currency('USD')
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'schedules.restaurant_id';
    }
}
