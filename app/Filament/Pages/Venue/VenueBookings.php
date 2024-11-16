<?php

namespace App\Filament\Pages\Venue;

use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VenueBookings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'gmdi-restaurant-menu-o';

    protected static string $view = 'filament.pages.venue-bookings';

    protected static ?string $slug = 'venue/bookings';

    protected static ?string $title = 'Daily Bookings';

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('venue');
    }

    public function table(Table $table): Table
    {
        /** @var Builder $query */
        $query = Booking::confirmedOrNoShow()
            ->selectRaw('MIN(bookings.id) as id, DATE(booking_at) as booking_date, COUNT(*) as number_of_bookings')
            ->addSelect(DB::raw('SUM(earnings.amount) as earnings'))
            ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
            ->where('earnings.type', 'venue')
            ->where('earnings.user_id', auth()->user()->id)
            ->groupBy(DB::raw('DATE(booking_at)'))
            ->orderByRaw('DATE(booking_at) DESC');

        return $table
            ->query($query)
            ->recordUrl(fn ($record) => VenueDailyBookings::getUrl(['venue' => auth()->user()->venue, 'date' => $record->booking_date]))
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
                    ->money(fn ($record) => $record->currency, divideBy: 100),
            ]);
    }
}
