<?php

namespace App\Filament\Pages\Restaurant;

use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class RestaurantBookings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'gmdi-restaurant-menu-o';

    protected static string $view = 'filament.pages.restaurant-bookings';

    protected static ?string $slug = 'restaurant/bookings';

    protected static ?string $title = 'Daily Bookings';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmed()
            ->selectRaw('MIN(bookings.id) as id, DATE(booking_at) as booking_date, COUNT(*) as number_of_bookings')
            ->addSelect(DB::raw('SUM(earnings.amount) as earnings'))
            ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
            ->where('earnings.type', 'restaurant')
            ->groupBy(DB::raw('DATE(booking_at)'))
            ->orderByRaw('DATE(booking_at) DESC');

        return $table
            ->query($query)
            ->recordUrl(fn ($record) => RestaurantDailyBookings::getUrl(['restaurant' => auth()->user()->restaurant, 'date' => $record->booking_date]))
            ->columns([
                TextColumn::make('booking_date')
                    ->date('D, M d, Y')
                    ->label('Booking Date'),
                TextColumn::make('number_of_bookings')
                    ->numeric()
                    ->alignRight()
                    ->label('Bookings'),
                TextColumn::make('earnings')
                    ->label('Earnings')
                    ->alignRight()
                    ->money('USD', divideBy: 100),
            ]);
    }
}
